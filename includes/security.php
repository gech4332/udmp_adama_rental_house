<?php
// CSRF protection helpers — must be included AFTER session_start().
// Use csrf_field() in every state-changing form, and csrf_validate()
// at the top of every state-changing handler.

function csrf_token(){
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(){
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Validate a POSTed CSRF token. On failure: mint a fresh token and show a
// friendly recovery page (or JSON for AJAX callers) instead of a dead end.
function csrf_validate(){
    $sess_token = $_SESSION['csrf_token'] ?? '';
    $post_token = (string)($_POST['csrf_token'] ?? '');
    if (!empty($sess_token) && !empty($post_token) && hash_equals($sess_token, $post_token)) {
        return;
    }

    // Stale token (form restored from cache/back-button, session refreshed,
    // logged out in another tab, ...). Mint a fresh one so the retry works.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // AJAX callers parse the body as JSON and show data.message in a toast.
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'type'    => 'error',
            'title'   => 'Session expired',
            'message' => 'This page was out of date. It has been refreshed - please try again.',
        ]);
        exit();
    }

    // Browser form post: send the user back to a fresh copy of the form.
    $back = $_SERVER['REQUEST_URI'] ?? '/';
    if (!is_string($back) || $back === '' || $back[0] !== '/' || strpos($back, '//') === 0) {
        $back = '/';
    }
    $back = htmlspecialchars($back, ENT_QUOTES, 'UTF-8');

    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Form expired</title><style>'
       . 'body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f6f8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
       . '.card{background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.08);padding:34px 30px;max-width:420px;text-align:center}'
       . 'h1{font-size:20px;color:#1b1f24;margin:0 0 10px}p{color:#5b6470;font-size:14.5px;line-height:1.55;margin:0 0 20px}'
       . 'a.btn{display:inline-block;background:#078930;color:#fff;text-decoration:none;font-weight:600;font-size:15px;padding:11px 22px;border-radius:9px}'
       . 'a.btn:hover{background:#066d26}</style></head><body><div class="card">'
       . '<h1>Form expired</h1>'
       . '<p>This page was out of date or you were signed in again in another tab. '
       . 'Nothing was lost - reload the form and try once more.</p>'
       . '<a class="btn" href="' . $back . '">Reload the page</a>'
       . '</div></body></html>';
    exit();
}

// ── Brute-force / credential-stuffing protection ───────────────────────
// Failed logins are recorded against both the submitted email and the
// client IP. If either identifier accumulates LOGIN_MAX_ATTEMPTS failures
// within LOGIN_LOCKOUT_MINUTES, further attempts are blocked. Successful
// logins clear the counters for that email/IP pair.

if (!defined('LOGIN_MAX_ATTEMPTS'))    define('LOGIN_MAX_ATTEMPTS', 5);
if (!defined('LOGIN_LOCKOUT_MINUTES')) define('LOGIN_LOCKOUT_MINUTES', 15);

function login_attempts_table(){
    global $conn;
    if (isset($conn)) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_identifier_time (identifier, attempt_at)
        )");
    }
}

function login_client_ip(){
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

// Number of recent failures for the email and for the client IP.
function login_failure_counts($email){
    global $conn;
    login_attempts_table();
    $window = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    $counts = [];
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM login_attempts WHERE identifier=? AND attempt_at > ?");
    if ($stmt) {
        foreach ([$email, login_client_ip()] as $identifier) {
            mysqli_stmt_bind_param($stmt, "ss", $identifier, $window);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $counts[$identifier] = $res ? (int)mysqli_fetch_row($res)[0] : 0;
        }
    }
    return $counts;
}

function login_locked_out($email){
    foreach (login_failure_counts($email) as $count) {
        if ($count >= LOGIN_MAX_ATTEMPTS) return true;
    }
    return false;
}

// Record a failed attempt for both identifiers and slow the attacker down.
function record_login_failure($email){
    global $conn;
    login_attempts_table();
    $stmt = mysqli_prepare($conn, "INSERT INTO login_attempts (identifier, attempt_at) VALUES (?, NOW())");
    if ($stmt) {
        foreach (array_unique([$email, login_client_ip()]) as $identifier) {
            mysqli_stmt_bind_param($stmt, "s", $identifier);
            mysqli_stmt_execute($stmt);
        }
    }
    usleep(random_int(300000, 800000));
}

// Clear counters after a successful authentication.
function clear_login_failures($email){
    global $conn;
    login_attempts_table();
    $ip = login_client_ip();
    $stmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE identifier=? OR identifier=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $email, $ip);
        mysqli_stmt_execute($stmt);
    }
}

function login_lockout_message(){
    return "Too many failed attempts. Please wait " . LOGIN_LOCKOUT_MINUTES . " minutes and try again.";
}

// ── Centralized authorization helpers (admin / user separation) ────────
// The authoritative admin rank is ALWAYS read from the database and is never
// trusted from the session alone. $_SESSION['is_admin'] is kept in sync with
// the database so a revoked or demoted admin loses access on their very next
// request instead of retaining a stale privilege for the whole session.

// The signed-in user's id from the session (0 when not logged in).
function current_user_id(){
    return (int)($_SESSION['user_id'] ?? 0);
}

// True when any user is signed in.
function is_logged_in(){
    return current_user_id() > 0;
}

// Authoritative admin rank (0 = landlord, 1 = admin, 2 = super admin), read
// from the users table and cached once per request.
function auth_db_admin_level(){
    global $conn;
    static $cached = null;
    if ($cached !== null) return $cached;
    $cached = 0;
    $uid = current_user_id();
    if ($uid > 0 && isset($conn)) {
        $stmt = mysqli_prepare($conn, "SELECT is_admin FROM users WHERE id=? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $uid);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            if ($row) $cached = (int)$row['is_admin'];
        }
    }
    return $cached;
}

// True when the signed-in user's *database* rank is at least $min_level.
// Also syncs $_SESSION['is_admin'] with the database (setting it when the
// user is an admin, removing the key when they are not) so every downstream
// role check in the page reflects the real, current role.
function admin_meets_level($min_level = 1){
    $level = auth_db_admin_level();
    if ($level >= 1) {
        $_SESSION['is_admin'] = $level;
    } else {
        unset($_SESSION['is_admin']);
    }
    return $level >= (int)$min_level;
}

// Standard page guard for admin screens. Preserves the long-standing
// behaviour of redirecting to login.php when the check fails.
function require_admin($min_level = 1){
    if (!admin_meets_level($min_level)) {
        header("Location: login.php");
        exit();
    }
}

// Standard page guard for member-only screens.
function require_login(){
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}