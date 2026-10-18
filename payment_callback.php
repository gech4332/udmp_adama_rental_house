<?php
// Payment return handler.
//  * test provider : POST from payment_checkout.php (CSRF-protected sandbox).
//  * chapa         : browser return; the result is re-checked SERVER-SIDE with
//                    the gateway and only then recorded. A success query
//                    parameter from the browser is never trusted on its own.
// Recording is idempotent (payment_mark_verified), so re-hitting this page
// cannot double-credit a payment.
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include_once('includes/payments.php');

$ref      = trim((string)($_REQUEST['ref'] ?? $_REQUEST['trx_ref'] ?? $_REQUEST['tx_ref'] ?? ''));
$mode     = strtolower(trim((string)($_POST['mode'] ?? '')));
$result   = strtolower(trim((string)($_POST['result'] ?? '')));

$payment = $ref !== '' ? payment_find_by_reference($conn, $ref) : null;
$redirect = 'manage_houses.php';

if(!$payment){
    header("Location: {$redirect}?msg=pay_invalid");
    exit();
}

$isTestPost = ($mode === 'test' && $_SERVER['REQUEST_METHOD'] === 'POST');

if($isTestPost){
    // Sandbox path: only a logged-in owner of this payment may confirm it.
    if(!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== (int)$payment['owner_id']){
        header("Location: login.php");
        exit();
    }
    csrf_validate();

    if($payment['provider'] !== 'test'){
        header("Location: {$redirect}?msg=pay_invalid");
        exit();
    }
    if($payment['status'] === 'verified'){
        header("Location: {$redirect}?msg=pay_verified");
        exit();
    }
    if($result === 'success'){
        payment_mark_verified($conn, $payment['reference'], $payment['reference'], 'test');
        header("Location: {$redirect}?msg=pay_ok");
    } else {
        payment_mark_failed($conn, $payment['reference'], 'failed');
        header("Location: {$redirect}?msg=pay_failed");
    }
    exit();
}

// Real gateway (Chapa) browser return — verify server-side.
if($payment['provider'] === 'chapa'){
    $verdict = payment_gateway_verify($conn, $payment['reference']);
    if(($verdict['status'] ?? '') === 'verified'){
        payment_mark_verified($conn, $payment['reference'], $verdict['tx_id'] ?? $payment['reference'], 'gateway');
        header("Location: {$redirect}?msg=pay_ok");
    } elseif(in_array($verdict['status'] ?? '', ['failed', 'cancelled', 'expired'], true)){
        payment_mark_failed($conn, $payment['reference'], $verdict['status']);
        header("Location: {$redirect}?msg=pay_failed");
    } else {
        header("Location: {$redirect}?msg=pay_pending");
    }
    exit();
}

header("Location: {$redirect}?msg=pay_pending");
exit();
