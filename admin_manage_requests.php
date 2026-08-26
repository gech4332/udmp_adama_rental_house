<?php
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header('Location: login.php');
    exit();
}

$requests = mysqli_query($conn, "SELECT * FROM requests WHERE status = 0 ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - AdamaRent Admin</title>
    <?php include(__DIR__ . '/header.php'); ?>
    <style>
        .content{margin-left:260px;padding:40px;width:calc(100% - 260px)}
        .content h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:24px;display:flex;align-items:center;gap:10px}
        .content h1 i{color:#ef4444}
        .request-card{background:#fff;border:1px solid #f1f5f9;border-radius:14px;padding:20px;margin-bottom:16px;display:flex;gap:20px;transition:all .3s}
        .request-card:hover{border-color:#e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.04)}
        .req-img{flex:0 0 200px}
        .req-img img{width:200px;height:140px;object-fit:cover;border-radius:10px}
        .req-img .no-img{width:200px;height:140px;background:#f1f5f9;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px}
        .req-body{flex:1}
        .req-body h3{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:6px}
        .req-meta{display:flex;flex-wrap:wrap;gap:16px;font-size:13px;color:#64748b;margin-bottom:10px}
        .req-meta strong{color:#374151}
        .req-actions{display:flex;flex-direction:column;gap:8px;align-items:flex-end;justify-content:center;min-width:120px}
        .btn-approve{padding:10px 20px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;font-family:inherit;transition:all .2s}
        .btn-approve:hover{box-shadow:0 4px 12px rgba(5,150,105,.4);transform:translateY(-1px)}
        .btn-reject{padding:10px 20px;background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.2);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;font-family:inherit;transition:all .2s}
        .btn-reject:hover{background:#ef4444;color:#fff;border-color:#ef4444}
        .empty-state{text-align:center;padding:60px 20px;background:#fff;border-radius:14px;border:1px solid #f1f5f9}
        .empty-state i{font-size:48px;color:#d1d5db;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:700;color:#374151;margin-bottom:8px}
        .empty-state p{color:#64748b;font-size:14px}
        .section-title{font-size:16px;font-weight:700;color:#0f172a;margin:24px 0 12px;display:flex;align-items:center;gap:8px}
        .section-title i{color:#0d9488}
    </style>
</head>
<body>
<div class="content">
    <h1><i class="fas fa-inbox"></i> Pending Approvals</h1>

    <?php if($requests && mysqli_num_rows($requests) > 0): ?>
        <?php while($req = mysqli_fetch_assoc($requests)): 
            $user_res = mysqli_query($conn, "SELECT full_name FROM users WHERE id = " . (int)$req['user_id']);
            $user = $user_res ? mysqli_fetch_assoc($user_res) : null;
            $house_res = mysqli_query($conn, "SELECT * FROM houses WHERE id = " . (int)$req['house_id']);
            $house = $house_res ? mysqli_fetch_assoc($house_res) : null;
        ?>
            <div class="request-card">
                <div class="req-img">
                    <?php if(!empty($house['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($house['image']); ?>" alt="Property">
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i> No Image</div>
                    <?php endif; ?>
                </div>
                <div class="req-body">
                    <h3><?php echo htmlspecialchars($house['description'] ?: $house['category'] ?? 'New Listing'); ?></h3>
                    <div class="req-meta">
                        <span><strong>Category:</strong> <?php echo htmlspecialchars($house['category'] ?? ''); ?></span>
                        <span><strong>Price:</strong> <?php echo number_format($house['amount'] ?? 0); ?> ETB</span>
                        <span><strong>Location:</strong> Kebele <?php echo htmlspecialchars($house['kebele'] ?? ''); ?>, <?php echo htmlspecialchars($house['street'] ?? ''); ?></span>
                    </div>
                    <div class="req-meta">
                        <span><strong>Posted by:</strong> <?php echo htmlspecialchars($user['full_name'] ?? 'Unknown'); ?></span>
                        <span><strong>Phone:</strong> <?php echo htmlspecialchars($house['phone'] ?? 'N/A'); ?></span>
                        <span><strong>Date:</strong> <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                    </div>
                </div>
                <div class="req-actions">
                    <a href="admin_actions.php?action=approve&id=<?php echo $req['id']; ?>" class="btn-approve"><i class="fas fa-check"></i> Approve</a>
                    <a href="admin_actions.php?action=reject&id=<?php echo $req['id']; ?>" class="btn-reject"><i class="fas fa-times"></i> Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>All caught up!</h3>
            <p>No pending requests to review at this time.</p>
        </div>
    <?php endif; ?>

    <?php
    $pending_houses_q = "SELECT h.*, u.full_name FROM houses h LEFT JOIN requests r ON r.house_id = h.id LEFT JOIN users u ON h.user_id = u.id WHERE (h.status = 'Pending' OR h.is_approved = 0 OR h.is_approved IS NULL) AND r.id IS NULL ORDER BY h.created_at DESC";
    $pending_res = mysqli_query($conn, $pending_houses_q);
    if($pending_res && mysqli_num_rows($pending_res) > 0): ?>
        <div class="section-title"><i class="fas fa-clock"></i> Pending Listings (without request record)</div>
        <?php while($house = mysqli_fetch_assoc($pending_res)): ?>
            <div class="request-card">
                <div class="req-img">
                    <?php if(!empty($house['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($house['image']); ?>" alt="Property">
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i> No Image</div>
                    <?php endif; ?>
                </div>
                <div class="req-body">
                    <h3><?php echo htmlspecialchars($house['description'] ?: 'New Listing'); ?></h3>
                    <div class="req-meta">
                        <span><strong>Category:</strong> <?php echo htmlspecialchars($house['category']); ?></span>
                        <span><strong>Price:</strong> <?php echo number_format($house['amount']); ?> ETB</span>
                        <span><strong>Location:</strong> Kebele <?php echo htmlspecialchars($house['kebele']); ?>, <?php echo htmlspecialchars($house['street']); ?></span>
                    </div>
                    <div class="req-meta">
                        <span><strong>Posted by:</strong> <?php echo htmlspecialchars($house['full_name'] ?? 'Unknown'); ?></span>
                        <span><strong>Date:</strong> <?php echo date('M d, Y', strtotime($house['created_at'])); ?></span>
                    </div>
                </div>
                <div class="req-actions">
                    <a href="admin_actions.php?action=approve_house&id=<?php echo $house['id']; ?>" class="btn-approve"><i class="fas fa-check"></i> Approve</a>
                    <a href="admin_actions.php?action=reject_house&id=<?php echo $house['id']; ?>" class="btn-reject"><i class="fas fa-times"></i> Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
