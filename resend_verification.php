<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');
include('includes/security.php');

if (!empty($_POST)) csrf_validate();

$email = trim($_POST['email'] ?? '');
if ($email === '' && isset($_SESSION['verify_pending_email'])) {
    $email = $_SESSION['verify_pending_email'];
}
$back = 'login.php';

if ($email === '') {
    header("Location: $back?resend=failed");
    exit();
}

if (!validate_email_before_send($email)['ok']) {
    header("Location: $back?resend=failed");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT id, full_name, email_verified FROM users WHERE email=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || !($user = mysqli_fetch_assoc($res))) {
    // Anti-enumeration: respond identically whether or not the account exists.
    header("Location: verify_pending.php?email=" . urlencode($email) . "&resend=sent");
    exit();
}
if ((int)$user['email_verified'] === 1) {
    // Anti-enumeration: respond identically to a fresh resend.
    header("Location: verify_pending.php?email=" . urlencode($email) . "&resend=sent");
    exit();
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 86400);
$uid = (int)$user['id'];
$stmt2 = mysqli_prepare($conn, "UPDATE users SET verify_token=?, verify_expires=? WHERE id=?");
mysqli_stmt_bind_param($stmt2, "ssi", $token, $expires, $uid);
mysqli_stmt_execute($stmt2);

$_SESSION['verify_pending_email'] = $email;
$mailResult = send_verification_email($email, $user['full_name'], $token);
$ep = 'email=' . urlencode($email);
if ($mailResult['ok'] === true) {
    header("Location: verify_pending.php?$ep&resend=sent");
} else {
    $why = ($mailResult['info'] === 'brevo ip not authorized') ? '&why=ip_auth' : '';
    header("Location: verify_pending.php?$ep&resend=failed$why");
}
exit();