<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) exit();

// Select ONLY pending requests (status 0)
$requests = mysqli_query($conn, "SELECT r.id as req_id, u.full_name, h.house_desc, h.price 
                                 FROM requests r 
                                 JOIN users u ON r.user_id = u.id 
                                 JOIN houses h ON r.house_id = h.id 
                                 WHERE r.status = 0");
?>
<!DOCTYPE html>
<html>
<head><title>Pending Approvals</title></head>
<body style="font-family: sans-serif; padding: 40px; background: #f8fafc;">
    <h2>Pending Requests Queue</h2>
    <?php while($r = mysqli_fetch_assoc($requests)): ?>
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong><?php echo $r['full_name']; ?></strong> listed <em><?php echo $r['house_desc']; ?></em>
            </div>
            <a href="admin_actions.php?action=approve&id=<?php echo $r['req_id']; ?>" 
               style="background: #10b981; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Approve & Verify</a>
        </div>
    <?php endwhile; ?>
</body>
</html>