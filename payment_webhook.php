<?php
// Payment gateway webhook. POST-only, idempotent.
//
// Security model:
//  1. If a webhook secret is configured, the raw body signature is checked.
//  2. Independently, the payment is ALWAYS re-verified server-side with the
//     gateway API before being marked verified — a forged POST that lacks a
//     valid signature (or claims success for an unpaid reference) is ignored.
//  3. Marking verified is idempotent, so duplicate deliveries are harmless.
include('includes/db.php');
include_once('includes/payments.php');

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');

// Parse JSON first, then fall back to form-encoded.
$data = json_decode((string)$raw, true);
if(!is_array($data)){
    $data = $_POST;
}

$reference = '';
foreach(['trx_ref', 'tx_ref', 'reference'] as $k){
    if(!empty($data[$k])){ $reference = trim((string)$data[$k]); break; }
}
if($reference === '' && !empty($data['data']) && is_array($data['data'])){
    foreach(['tx_ref', 'trx_ref', 'reference'] as $k){
        if(!empty($data['data'][$k])){ $reference = trim((string)$data['data'][$k]); break; }
    }
}

if($reference === ''){
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Missing reference']);
    exit;
}

$payment = payment_find_by_reference($conn, $reference);
if(!$payment){
    // Unknown reference — acknowledge so the gateway does not retry forever.
    echo json_encode(['status' => true, 'message' => 'Ignored unknown reference']);
    exit;
}

// 1. Optional signature check (defence in depth).
if(defined('CHAPA_WEBHOOK_SECRET') && trim((string)CHAPA_WEBHOOK_SECRET) !== ''){
    $sig = $_SERVER['HTTP_X_CHAPA_SIGNATURE'] ?? '';
    $expected = hash_hmac('sha256', (string)$raw, (string)CHAPA_WEBHOOK_SECRET);
    if($sig === '' || !hash_equals($expected, $sig)){
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Invalid signature']);
        exit;
    }
}

// 2. Server-side re-verification (do not trust the payload's status claim).
$verdict = payment_gateway_verify($conn, $reference);
$status = $verdict['status'] ?? 'pending';

if($status === 'verified'){
    payment_mark_verified($conn, $reference, $verdict['tx_id'] ?? $reference, 'gateway');
} elseif(in_array($status, ['failed', 'cancelled', 'expired'], true)){
    payment_mark_failed($conn, $reference, $status);
}

echo json_encode(['status' => true, 'payment' => $status]);
exit;
