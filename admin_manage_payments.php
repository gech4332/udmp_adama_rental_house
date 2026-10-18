<?php
// Admin: owner service-fee payments. Lists payment attempts and lets an admin
// MANUALLY verify an offline payment. Manual verification is recorded with
// verification_source='manual' so it stays clearly distinct from a
// gateway-verified payment.
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/lang.php');
include('includes/security.php');
include_once('includes/payments.php');

if(!admin_meets_level(1)){
    header('Location: admin_login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify'){
    csrf_validate();
    $pid = (int)($_POST['payment_id'] ?? 0);
    $payment = payment_find_by_id($conn, $pid);
    if($payment && $payment['status'] !== 'verified'){
        $admin = $_SESSION['user_name'] ?? ('admin#' . (int)$_SESSION['user_id']);
        payment_mark_verified($conn, $payment['reference'], 'manual:' . $admin, 'manual');
        header('Location: admin_manage_payments.php?msg=verified');
        exit();
    }
    header('Location: admin_manage_payments.php?msg=noop');
    exit();
}

$filter = ($_GET['status'] ?? '') === 'pending' ? 'pending' : '';
$sql = "SELECT rp.*, rr.status AS req_status, h.kebele, h.category,
               o.full_name AS owner_name, t.full_name AS tenant_name
        FROM rental_payments rp
        JOIN rental_requests rr ON rp.rental_request_id = rr.id
        JOIN houses h ON rp.house_id = h.id
        LEFT JOIN users o ON rp.owner_id = o.id
        LEFT JOIN users t ON rp.tenant_id = t.id";
if($filter === 'pending') $sql .= " WHERE rp.status='pending'";
$sql .= " ORDER BY rp.id DESC LIMIT 200";
$payments = mysqli_query($conn, $sql);

$flash = [
    'verified' => ['Payment marked as verified (manual).', 'green'],
    'noop'     => ['No change made.', 'red'],
];
$msg = isset($_GET['msg'], $flash[$_GET['msg']]) ? $flash[$_GET['msg']] : null;

function pay_badge($status){
    switch($status){
        case 'verified':  return ['green', 'Verified'];
        case 'pending':   return ['amber', 'Pending'];
        case 'failed':    return ['red', 'Failed'];
        case 'cancelled': return ['red', 'Cancelled'];
        case 'expired':   return ['gray', 'Expired'];
        default:          return ['gray', ucfirst((string)$status)];
    }
}
?>
<?php include(__DIR__ . '/includes/header.php'); ?>
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-money-bill-transfer"></i></div>
            <div>
                <h1>Service-Fee Payments</h1>
                <div class="page-sub">Owner fee payments (2% of first month's rent). Manually verify offline payments here.</div>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="flash flash-<?php echo $msg[1]; ?>">
            <i class="fas fa-<?php echo $msg[1] === 'green' ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo htmlspecialchars($msg[0]); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom:16px;display:flex;gap:10px">
        <a href="admin_manage_payments.php" class="btn" style="text-decoration:none;padding:9px 16px;border-radius:9px;background:<?php echo $filter === '' ? '#078930' : '#f1f5f9'; ?>;color:<?php echo $filter === '' ? '#fff' : '#475569'; ?>;font-weight:600;font-size:13px">All</a>
        <a href="admin_manage_payments.php?status=pending" class="btn" style="text-decoration:none;padding:9px 16px;border-radius:9px;background:<?php echo $filter === 'pending' ? '#078930' : '#f1f5f9'; ?>;color:<?php echo $filter === 'pending' ? '#fff' : '#475569'; ?>;font-weight:600;font-size:13px">Pending only</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Owner</th>
                    <th>Property</th>
                    <th>Rent</th>
                    <th>Fee</th>
                    <th>Gateway</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($payments && mysqli_num_rows($payments) > 0): while($p = mysqli_fetch_assoc($payments)): list($cls, $lbl) = pay_badge($p['status']); ?>
                <tr>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['owner_name'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($p['category'] ?? ''); ?> · <?php echo htmlspecialchars(t('req_in_kebele')); ?> <?php echo htmlspecialchars($p['kebele'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(payment_money_label($p['rent_snapshot'])); ?></td>
                    <td><strong><?php echo htmlspecialchars(payment_money_label($p['fee_amount'])); ?></strong></td>
                    <td><?php echo htmlspecialchars(payment_provider_label($p['provider'])); ?></td>
                    <td style="font-size:12px"><?php echo htmlspecialchars($p['reference']); ?></td>
                    <td>
                        <span class="badge" style="background:<?php echo $cls === 'green' ? 'rgba(16,185,129,.12)' : ($cls === 'amber' ? 'rgba(245,158,11,.12)' : ($cls === 'red' ? 'rgba(239,68,68,.12)' : 'rgba(100,116,139,.12)')); ?>;color:<?php echo $cls === 'green' ? '#059669' : ($cls === 'amber' ? '#d97706' : ($cls === 'red' ? '#dc2626' : '#64748b')); ?>"><?php echo htmlspecialchars($lbl); ?></span>
                        <?php if($p['verification_source'] === 'manual'): ?><div style="font-size:10px;color:#94a3b8;margin-top:3px">manual</div><?php endif; ?>
                    </td>
                    <td style="font-size:12px"><?php echo htmlspecialchars(date('M j, Y g:i a', strtotime($p['created_at']))); ?></td>
                    <td>
                        <?php if($p['status'] !== 'verified'): ?>
                        <form method="POST" onsubmit="return confirm('Mark this payment as verified (manual)? Only do this if you have independently confirmed the owner paid the fee.');" style="display:inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="verify">
                            <input type="hidden" name="payment_id" value="<?php echo (int)$p['id']; ?>">
                            <button type="submit" style="padding:7px 14px;border-radius:8px;border:none;background:rgba(16,185,129,.12);color:#059669;font-weight:700;font-size:12px;cursor:pointer">Verify</button>
                        </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:40px">No payments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
