<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header("Location: admin_login.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_rank = $_SESSION['is_admin']; 

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

if($role_filter == 'staff') {
    $page_title = "Internal Staff";
    $query = "SELECT * FROM users WHERE is_admin >= 1 ORDER BY is_admin DESC";
} elseif($role_filter == 'landlords') {
    $page_title = "Landlords";
    $query = "SELECT * FROM users WHERE is_admin = 0 ORDER BY id DESC";
} else {
    $page_title = "All Users";
    $query = "SELECT * FROM users ORDER BY is_admin DESC, id DESC";
}

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AdamaRent Admin</title>
    <?php include(__DIR__ . '/header.php'); ?>
    <style>
        .content{margin-left:260px;padding:40px;width:calc(100% - 260px)}
        .content h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:24px;display:flex;align-items:center;gap:10px}
        .content h1 i{color:#0d9488}
        .badge-role{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
        .role-super{background:rgba(139,92,246,.1);color:#7c3aed}
        .role-admin{background:rgba(245,158,11,.1);color:#d97706}
        .role-landlord{background:rgba(100,116,139,.1);color:#475569}
        .btn-sm{padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;font-family:inherit;transition:all .2s}
        .btn-edit{background:rgba(13,148,136,.1);color:#0d9488}
        .btn-edit:hover{background:#0d9488;color:#fff}
        .btn-delete{background:rgba(239,68,68,.08);color:#dc2626}
        .btn-delete:hover{background:#ef4444;color:#fff}
        .btn-promote{background:rgba(16,185,129,.1);color:#059669}
        .btn-promote:hover{background:#059669;color:#fff}
        .btn-revoke{background:rgba(245,158,11,.1);color:#d97706}
        .btn-revoke:hover{background:#f59e0b;color:#fff}
    </style>
</head>
<body>
<div class="content">
    <h1><i class="fas fa-users"></i> <?php echo $page_title; ?></h1>
    
    <div class="data-card">
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <?php if($my_rank == 2): ?><th>Permissions</th><?php endif; ?>
                    <th>Actions</th>
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
                        <?php 
                        $role_class = 'role-landlord';
                        $role_text = 'LANDLORD';
                        if($target_rank == 2) { $role_class = 'role-super'; $role_text = 'SUPER ADMIN'; }
                        elseif($target_rank == 1) { $role_class = 'role-admin'; $role_text = 'ADMIN'; }
                        ?>
                        <span class="badge-role <?php echo $role_class; ?>"><?php echo $role_text; ?></span>
                    </td>
                    
                    <?php if($my_rank == 2): ?>
                    <td>
                        <?php if($target_id != $my_id): ?>
                            <form action="update_permission_v2.php" method="POST" style="display:inline">
                                <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
                                <?php if($target_rank == 0): ?>
                                    <button name="action" value="1" class="btn-sm btn-promote"><i class="fas fa-arrow-up"></i> Make Admin</button>
                                <?php else: ?>
                                    <button name="action" value="0" class="btn-sm btn-revoke"><i class="fas fa-arrow-down"></i> Revoke</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>

                    <td>
                        <a href="edit_user.php?id=<?php echo $target_id; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                        <?php if($target_id != $my_id): ?>
                        <form action="delete_user.php" method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
                            <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
                            <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
