<?php
// TEST/SANDBOX checkout page. Only reachable when the payment's provider is
// "test" (i.e. no real gateway credentials are configured). It lets a local
// developer simulate a successful or failed payment WITHOUT moving real money.
// It is clearly labelled and never pretends to be a real gateway.
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include('includes/lang.php');
include_once('includes/payments.php');
if(!isset($_SESSION['csrf_token'])) csrf_token();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$me = (int)$_SESSION['user_id'];
$ref = trim((string)($_GET['ref'] ?? ''));
$payment = $ref !== '' ? payment_find_by_reference($conn, $ref) : null;

$allowed = $payment
    && $payment['provider'] === 'test'
    && (int)$payment['owner_id'] === $me
    && $payment['status'] === 'pending';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('pay_test_title')); ?> - EthioRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .box{max-width:480px;width:100%;background:#fff;border-radius:18px;border:1px solid #f1f5f9;box-shadow:0 12px 40px -18px rgba(15,23,42,.25);overflow:hidden}
        .box-head{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;padding:22px 26px}
        .box-head h1{font-size:18px;font-weight:800;display:flex;align-items:center;gap:9px}
        .test-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(252,221,9,.16);color:#FCDD09;border:1px solid rgba(252,221,9,.35);padding:4px 10px;border-radius:50px;font-size:11px;font-weight:700;margin-top:10px}
        .box-body{padding:24px 26px}
        .row{display:flex;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
        .row:last-of-type{border-bottom:none}
        .row span{color:#94a3b8}
        .row strong{color:#0f172a;font-weight:700;text-align:right}
        .total{background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;margin:16px 0 20px}
        .total .lbl{font-size:13px;color:#64748b;font-weight:600}
        .total .amt{font-size:22px;font-weight:800;color:#078930}
        .actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .actions button{border:none;border-radius:11px;padding:14px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
        .btn-ok{background:linear-gradient(135deg,#078930,#0da344);color:#fff}
        .btn-ok:hover{box-shadow:0 6px 20px rgba(7,137,48,.4);transform:translateY(-1px)}
        .btn-fail{background:rgba(239,68,68,.08);color:#dc2626;border:1px solid rgba(239,68,68,.2) !important}
        .btn-fail:hover{background:#ef4444;color:#fff}
        .note{margin-top:18px;font-size:12.5px;color:#64748b;line-height:1.6;background:#FEFCE8;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px}
        .note i{color:#d97706;margin-right:5px}
        .invalid{text-align:center;padding:40px 20px}
        .invalid i{font-size:44px;color:#cbd5e1;margin-bottom:14px}
        .invalid h2{font-size:18px;margin-bottom:8px}
        .invalid p{color:#64748b;font-size:14px;margin-bottom:20px}
        .invalid a{display:inline-block;padding:12px 22px;background:#0f172a;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px}
    </style>
</head>
<body>
    <div class="box">
        <?php if(!$allowed): ?>
            <div class="box-body invalid">
                <i class="fas fa-circle-exclamation"></i>
                <h2><?php echo htmlspecialchars(t('pay_test_invalid_h')); ?></h2>
                <p><?php echo htmlspecialchars(t('pay_test_invalid_p')); ?></p>
                <a href="manage_houses.php"><?php echo htmlspecialchars(t('nav_dashboard')); ?></a>
            </div>
        <?php else: ?>
            <div class="box-head">
                <h1><i class="fas fa-flask"></i> <?php echo htmlspecialchars(t('pay_test_title')); ?></h1>
                <span class="test-tag"><i class="fas fa-triangle-exclamation"></i> <?php echo htmlspecialchars(t('pay_test_tag')); ?></span>
            </div>
            <div class="box-body">
                <div class="row"><span><?php echo htmlspecialchars(t('pay_first_month_rent')); ?></span><strong><?php echo htmlspecialchars(payment_money_label($payment['rent_snapshot'])); ?></strong></div>
                <div class="row"><span><?php echo htmlspecialchars(t('pay_service_fee')); ?> (<?php echo rtrim(rtrim(number_format(payment_fee_rate() * 100, 2), '0'), '.'); ?>%)</span><strong><?php echo htmlspecialchars(payment_money_label($payment['fee_amount'])); ?></strong></div>
                <div class="row"><span><?php echo htmlspecialchars(t('pay_reference')); ?></span><strong style="font-size:12px"><?php echo htmlspecialchars($payment['reference']); ?></strong></div>

                <div class="total">
                    <span class="lbl"><?php echo htmlspecialchars(t('pay_amount_due')); ?></span>
                    <span class="amt"><?php echo htmlspecialchars(payment_money_label($payment['fee_amount'])); ?></span>
                </div>

                <form method="POST" action="payment_callback.php" class="actions">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ref" value="<?php echo htmlspecialchars($payment['reference']); ?>">
                    <input type="hidden" name="mode" value="test">
                    <button type="submit" name="result" value="success" class="btn-ok"><i class="fas fa-check"></i> <?php echo htmlspecialchars(t('pay_test_success')); ?></button>
                    <button type="submit" name="result" value="failed" class="btn-fail"><i class="fas fa-xmark"></i> <?php echo htmlspecialchars(t('pay_test_fail')); ?></button>
                </form>

                <div class="note"><i class="fas fa-circle-info"></i><?php echo htmlspecialchars(t('pay_test_note')); ?></div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
