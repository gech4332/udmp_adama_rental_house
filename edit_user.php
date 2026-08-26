<?php
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    die('Access Denied');
}

$my_level = (int)($_SESSION['is_admin'] ?? 0);
$my_id = (int)($_SESSION['user_id'] ?? 0);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $target_id = (int)$_POST['user_id'];
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));

    $res = mysqli_query($conn, "SELECT is_admin FROM users WHERE id=$target_id");
    if(!$res || mysqli_num_rows($res) === 0){ die('User not found'); }
    $target = mysqli_fetch_assoc($res);
    $target_level = (int)$target['is_admin'];

    if($my_level === 1 && $target_level >= 1 && $target_id !== $my_id){
        die('Only Super Admins can edit other admin accounts.');
    }

    mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email' WHERE id=$target_id");
    header('Location: admin_manage_users.php?updated=1');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = mysqli_query($conn, "SELECT id, full_name, email, is_admin FROM users WHERE id=$id");
if(!$res || mysqli_num_rows($res) === 0){ die('User not found'); }
$user = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - AdamaRent Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;display:flex;min-height:100vh}
        .content{margin-left:260px;padding:40px;width:calc(100% - 260px)}
        .form-card{background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:32px;max-width:500px}
        .form-card h2{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .form-card h2 i{color:#0d9488}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group input{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s}
        .form-group input:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.08)}
        .btn-row{display:flex;gap:10px;margin-top:24px}
        .btn-save{padding:10px 20px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .2s}
        .btn-save:hover{box-shadow:0 4px 12px rgba(13,148,136,.4)}
        .btn-cancel{padding:10px 20px;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s}
        .btn-cancel:hover{background:#e2e8f0}
    </style>
</head>
<body>
    <?php include(__DIR__ . '/sidebar.php'); ?>
    <div class="content">
        <div class="form-card">
            <h2><i class="fas fa-user-edit"></i> Edit User</h2>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="admin_manage_users.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
