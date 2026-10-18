<?php
// Owner initiates the 2% service-fee payment for a rental request.
// Login + CSRF + ownership are all verified server-side before anything
// is charged. The amount comes from the stored rent snapshot, never the
// browser. A retry reuses the existing pending payment.
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include_once('includes/payments.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php?redirect=" . urlencode('manage_houses.php'));
    exit();
}

$me = (int)$_SESSION['user_id'];
$request_id = (int)($_POST['request'] ?? $_GET['request'] ?? 0);

if($request_id <= 0 || $_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: manage_houses.php");
    exit();
}

csrf_validate();

// Only the owner of the house behind this pending request may pay.
$chk = mysqli_prepare($conn, "
    SELECT rr.id
    FROM rental_requests rr
    JOIN houses h ON rr.house_id = h.id
    WHERE rr.id=? AND h.user_id=? AND rr.status='pending'");
mysqli_stmt_bind_param($chk, "ii", $request_id, $me);
mysqli_stmt_execute($chk);
$cres = mysqli_stmt_get_result($chk);
if(!($cres && mysqli_fetch_assoc($cres))){
    header("Location: manage_houses.php?msg=pay_invalid");
    exit();
}

list($payment, $state) = payment_get_or_create($conn, $request_id);
if(!$payment){
    header("Location: manage_houses.php?msg=pay_error");
    exit();
}

// Already verified — nothing left to pay.
if($payment['status'] === 'verified'){
    header("Location: manage_houses.php?msg=pay_verified");
    exit();
}

$result = payment_gateway_initiate($conn, $payment);

if(!$result['ok']){
    header("Location: manage_houses.php?msg=pay_error");
    exit();
}

if($result['mode'] === 'redirect' && $result['pay_url'] !== ''){
    payment_set_pay_url($conn, (int)$payment['id'], $result['pay_url'], $payment['provider']);
    header("Location: " . $result['pay_url']);
    exit();
}

// Manual fallback: recorded as pending, an admin verifies it later.
header("Location: manage_houses.php?msg=pay_pending");
exit();
