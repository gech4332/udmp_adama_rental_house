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

// Validate a POSTed CSRF token. On failure prints a message and dies.
function csrf_validate(){
    $sess_token = $_SESSION['csrf_token'] ?? '';
    $post_token = (string)($_POST['csrf_token'] ?? '');
    if (empty($sess_token) || empty($post_token) || !hash_equals($sess_token, $post_token)) {
        http_response_code(403);
        die("Invalid or expired form token. Please go back, reload the page, and try again.");
    }
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