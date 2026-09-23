<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');

function google_redirect($target = 'login.php?google=error') {
    header("Location: $target");
    exit();
}

// CSRF state check
$err_state = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_oauth_state']);
$state = $_GET['state'] ?? '';
if ($state === '' || $err_state === '' || !hash_equals($err_state, $state)) {
    google_redirect('login.php?google=state');
}
if (isset($_GET['error'])) {
    google_redirect('login.php?google=denied');
}
if (!isset($_GET['code'])) {
    google_redirect('login.php?google=error');
}
if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    google_redirect('login.php?google=unconfigured');
}

// Exchange the authorization code for tokens
$redirect_uri = app_base_url() . '/google_callback.php';
$post = http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code',
]);
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT => 20,
]);
$tok_body = curl_exec($ch);
$tok_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tok = json_decode((string)$tok_body, true);
if ($tok_code < 200 || $tok_code >= 300 || empty($tok['id_token'])) {
    google_redirect('login.php?google=token');
}

// Validate the ID token with Google (issuer, audience, expiry, email, verified)
$info_body = file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($tok['id_token']));
$info = json_decode((string)$info_body, true);
if (empty($info['email']) || ($info['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    google_redirect('login.php?google=invalid');
}
if (isset($info['exp']) && (int)$info['exp'] < time()) {
    google_redirect('login.php?google=expired');
}
$valid_iss = ['accounts.google.com', 'https://accounts.google.com'];
if (!in_array($info['iss'] ?? '', $valid_iss, true)) {
    google_redirect('login.php?google=invalid');
}
if ((int)($info['email_verified'] ?? 0) !== 1) {
    google_redirect('login.php?google=unverified');
}

$g_email = $info['email'];
$g_name = $info['name'] ?? (strstr($g_email, '@', true) ?: $g_email);

// Link by email: create account if missing, or attach verification if present
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $g_email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && ($user = mysqli_fetch_assoc($res))) {
    // Google proved this email — mark verified if it wasn't
    if ((int)$user['email_verified'] === 0) {
        $uid = (int)$user['id'];
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET email_verified=1, verify_token=NULL, verify_expires=NULL WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $uid);
        mysqli_stmt_execute($stmt2);
        $user['email_verified'] = 1;
    }
    if ((int)$user['is_admin'] === 0 && $user['full_name'] === '') {
        $uid = (int)$user['id'];
        $stmt3 = mysqli_prepare($conn, "UPDATE users SET full_name=? WHERE id=?");
        mysqli_stmt_bind_param($stmt3, "si", $g_name, $uid);
        mysqli_stmt_execute($stmt3);
        $user['full_name'] = $g_name;
    }
} else {
    $pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt4 = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, email_verified) VALUES (?, ?, ?, 1)");
    mysqli_stmt_bind_param($stmt4, "sss", $g_name, $g_email, $pass);
    if (!mysqli_stmt_execute($stmt4)) {
        google_redirect('login.php?google=error');
    }
    $stmt5 = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt5, "s", $g_email);
    mysqli_stmt_execute($stmt5);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt5));
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
session_regenerate_id(true);

// Check for a pending admin invite for this user
$uid = (int)$user['id'];
$inv_stmt = mysqli_prepare($conn, "SELECT id FROM admin_invites WHERE user_id=? AND status='pending' LIMIT 1");
mysqli_stmt_bind_param($inv_stmt, "i", $uid);
mysqli_stmt_execute($inv_stmt);
$check_invite = mysqli_stmt_get_result($inv_stmt);
if($check_invite && mysqli_num_rows($check_invite) > 0){
    $_SESSION['pending_admin_key'] = 1;
    header("Location: admin_key.php");
    exit();
}

if ((int)$user['is_admin'] >= 1) {
    $_SESSION['is_admin'] = (int)$user['is_admin'];
    header("Location: admin_panel.php");
} else {
    header("Location: index.php");
}
exit();