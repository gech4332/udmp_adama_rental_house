<?php
session_start();
include('db.php');

// Temporarily enable error display for debugging this page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header('Location: admin_login.php');
    exit();
}

// Select ONLY pending requests (status 0)
$requests = mysqli_query($conn, "SELECT * FROM requests WHERE status = 0 ORDER BY created_at DESC");

if ($requests === false) {
    echo '<h3>Error loading requests</h3>';
    echo '<pre>' . htmlspecialchars(mysqli_error($conn)) . '</pre>';
    exit();
}



// We'll fetch related user and house rows per request to avoid complex JOIN errors

?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Admin Console | Adama Rent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
<title>Pending Approvals</title></head>
<body style="font-family: sans-serif; padding: 40px; background: #f8fafc;">
    
    <main class="main-content">
    <button onclick="history.back()" style="background:#fff;border:1px solid #e2e8f0;padding:8px 10px;border-radius:6px;cursor:pointer;margin-bottom:12px;font-weight:600;"><i class="fas fa-arrow-left"></i> Back</button>
    <h2>Pending Requests Queue</h2>
    <?php if(mysqli_num_rows($requests) == 0): ?>
        <p>No pending requests.</p>
    <?php else: ?>
        <?php while($req = mysqli_fetch_assoc($requests)): 
                // Fetch user and house rows safely
                $user_res = mysqli_query($conn, "SELECT full_name FROM users WHERE id = " . (int)$req['user_id']);
                $user = ($user_res) ? mysqli_fetch_assoc($user_res) : null;
                $house_res = mysqli_query($conn, "SELECT * FROM houses WHERE id = " . (int)$req['house_id']);
                $house = ($house_res) ? mysqli_fetch_assoc($house_res) : null;
                
                // Build a safe array with defaults
                $r = [
                    'req_id' => $req['id'],
                    'req_created' => $req['created_at'],
                    'full_name' => $user['full_name'] ?? 'Unknown',
                    'user_phone' => $user['phone'] ?? '',
                    'image' => $house['image'] ?? '',
                    'kebele' => $house['kebele'] ?? '',
                    'street' => $house['street'] ?? '',
                    'house_number' => $house['house_number'] ?? '',
                    'category' => $house['category'] ?? '',
                    'video_file' => $house['video_file'] ?? '',
                    'map_link' => $house['map_link'] ?? '',
                    'house_desc' => $house['description'] ?? '',
                    'price' => $house['amount'] ?? 0,
                    'phone' => $house['phone'] ?? '',
                    // 'delete_key' intentionally removed for privacy
                    // 'delete_key' => $house['delete_key'] ?? '',
                    'house_id' => $house['id'] ?? 0
                ];
        ?>
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; display: flex; gap: 20px;">
                <div style="flex:0 0 220px;">
                    <?php if(!empty($r['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($r['image']); ?>" style="width: 220px; height:140px; object-fit:cover; border-radius:6px;">
                    <?php else: ?>
                        <div style="width:220px;height:140px;background:#eee;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#777;">No Image</div>
                    <?php endif; ?>
                    <?php if(!empty($r['video_file'])): ?>
                        <div style="margin-top:8px;text-align:center;"><a href="uploads/<?php echo htmlspecialchars($r['video_file']); ?>" target="_blank" style="text-decoration:none; color:#4b7b8a;">▶ View Video</a></div>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <h3 style="margin:0 0 6px;"><?php echo htmlspecialchars($r['house_desc'] ?: 'No title'); ?></h3>
                    <p style="margin:0 0 6px; color:#555;"><strong>Category:</strong> <?php echo htmlspecialchars($r['category']); ?>
                       &nbsp;|&nbsp; <strong>Price:</strong> <?php echo number_format($r['price']); ?> ETB</p>
                    <p style="margin:6px 0; color:#444;"><strong>Location:</strong> Kebele <?php echo htmlspecialchars($r['kebele']); ?>, <?php echo htmlspecialchars($r['street']); ?> <?php echo htmlspecialchars($r['house_number']); ?></p>

                    <p style="margin:6px 0; color:#333;"><strong>Contact Phone:</strong> <?php echo htmlspecialchars($r['phone'] ?: 'Not provided'); ?>
                    &nbsp;|&nbsp; <strong>Posted By:</strong> <?php echo htmlspecialchars($r['full_name']); ?> (<?php echo htmlspecialchars($r['user_phone']); ?>)</p>

                        <p><a href="<?php echo htmlspecialchars($r['map_link']); ?>" target="_blank">View on Map</a></p>
                       <p style="font-size:12px;color:#888;margin-top:8px;">Posted on: <?php echo date('M d, Y H:i', strtotime($r['req_created'])); ?></p>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;min-width:140px;">
                    <a href="admin_actions.php?action=approve&id=<?php echo $r['req_id']; ?>" style="background:#10b981;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">Approve</a>
                    <a href="admin_actions.php?action=reject&id=<?php echo $r['req_id']; ?>" style="background:#dc3545;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <?php
    // Also show houses with status 'Pending' that don't have a request row
    $pending_houses_q = "SELECT h.*, u.full_name FROM houses h LEFT JOIN requests r ON r.house_id = h.id LEFT JOIN users u ON h.user_id = u.id WHERE (h.status = 'Pending' OR h.is_approved = 0 OR h.is_approved IS NULL) AND r.id IS NULL ORDER BY h.created_at DESC";
    $pending_res = mysqli_query($conn, $pending_houses_q);
    $pending_count = ($pending_res) ? mysqli_num_rows($pending_res) : 0;
    if ($pending_res && mysqli_num_rows($pending_res) > 0): ?>
        <h3 style="margin-top:20px;">Pending Listings (no request record)</h3>
        <?php while($house = mysqli_fetch_assoc($pending_res)): ?>
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; display: flex; gap: 20px;">
                <div style="flex:0 0 180px;">
                    <?php if(!empty($house['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($house['image']); ?>" style="width: 180px; height:120px; object-fit:cover; border-radius:6px;">
                    <?php else: ?>
                        <div style="width:180px;height:120px;background:#eee;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#777;">No Image</div>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <h3 style="margin:0 0 6px;"><?php echo htmlspecialchars($house['description'] ?? 'No description'); ?></h3>
                    <p style="margin:0; color:#555;"><strong>Category:</strong> <?php echo htmlspecialchars($house['category']); ?> | <strong>Price:</strong> <?php echo number_format($house['amount']); ?> ETB</p>
                    <p style="margin:6px 0; color:#444;"><strong>Location:</strong> Kebele <?php echo htmlspecialchars($house['kebele']); ?>, <?php echo htmlspecialchars($house['street']); ?> <?php echo htmlspecialchars($house['house_number']); ?></p>
                    <p style="margin:6px 0; color:#333;"><strong>Contact Phone:</strong> <?php echo htmlspecialchars($house['phone'] ?? 'Not provided'); ?></p>
                    <?php if(!empty($house['map_link'])): ?>
                        <p><a href="<?php echo htmlspecialchars($house['map_link']); ?>" target="_blank">View on Map</a></p>
                    <?php endif; ?>
                    <p style="font-size:12px;color:#888;margin-top:8px;">Posted by <?php echo htmlspecialchars($house['full_name'] ?? 'Unknown'); ?> on <?php echo date('M d, Y H:i', strtotime($house['created_at'] ?? date('Y-m-d H:i'))); ?></p>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                    <a href="admin_actions.php?action=approve_house&id=<?php echo $house['id']; ?>" style="background:#10b981;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">Approve</a>
                    <a href="admin_actions.php?action=reject_house&id=<?php echo $house['id']; ?>" style="background:#dc3545;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">Reject</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

</main>
</body>
</html>