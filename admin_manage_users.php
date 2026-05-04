<?php
session_start();
include('db.php');

// Security Guard
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header("Location: login.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_rank = $_SESSION['is_admin']; 

// --- FILTER LOGIC ---
// Check if a filter was passed in the URL (e.g., ?role=staff)
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

if($role_filter == 'staff') {
    $page_title = "Internal Staff Control";
    $query = "SELECT * FROM users WHERE is_admin >= 1 ORDER BY is_admin DESC";
} elseif($role_filter == 'landlords') {
    $page_title = "Landlord Database";
    $query = "SELECT * FROM users WHERE is_admin = 0 ORDER BY id DESC";
} else {
    $page_title = "System User Control";
    $query = "SELECT * FROM users ORDER BY is_admin DESC, id DESC";
}

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> | Adama Rent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        .sidebar { width: 260px; background: #2c3e50; color: white; height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; }
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #4b7b8a; color: white; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-edit { background: #3498db; }
        .btn-delete { background: #e74c3c; }
        .btn-permission { background: #27ae60; }
        .btn-revoke { background: #f39c12; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-2 { background: #8e44ad; color: white; }
        .badge-1 { background: #e67e22; color: white; }
        .badge-0 { background: #bdc3c7; color: #2c3e50; }
        .nav-item { padding: 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 5px; margin-bottom: 5px; }
        .nav-item:hover, .active { background: #34495e; color: white; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3><?php echo ($my_rank == 2) ? "Super Admin" : "Admin"; ?></h3>
    <hr style="border: 0.5px solid #34495e;">
    <a href="admin_panel.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="admin_manage_houses.php" class="nav-item"><i class="fas fa-home"></i> Manage Listings</a>
    <a href="admin_manage_users.php" class="nav-item active"><i class="fas fa-users"></i> Manage Users</a>
    <a href="logout.php" style="color: #e74c3c; padding: 15px; display:block; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h1><?php echo $page_title; ?></h1>
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Role</th>
                <?php if($my_rank == 2): ?> <th>Permissions</th> <?php endif; ?>
                <th>Management</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): 
                $target_id = $row['id'];
                $target_rank = $row['is_admin'];
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['full_name'] ?? 'User'); ?></strong></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <span class="badge badge-<?php echo $target_rank; ?>">
                        <?php 
                            if($target_rank == 2) echo "SUPER";
                            elseif($target_rank == 1) echo "ADMIN";
                            else echo "LANDLORD";
                        ?>
                    </span>
                </td>
                
                <?php if($my_rank == 2): ?>
                <td>
                    <?php if($target_id != $my_id): ?>
                        <form action="update_permission_v2.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
                            <?php if($target_rank == 0): ?>
                                <button name="action" value="1" class="btn btn-permission">Make Admin</button>
                            <?php else: ?>
                                <button name="action" value="0" class="btn btn-revoke">Revoke</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <td>
                    <a href="edit_user.php?id=<?php echo $target_id; ?>" class="btn btn-edit"><i class="fas fa-edit"></i></a>
                    <?php if($target_id != $my_id): ?>
                    <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete user?');">
                        <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
                        <button type="submit" class="btn btn-delete"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>