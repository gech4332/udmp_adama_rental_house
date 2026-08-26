<?php
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

    // Fetch target user
    $res = mysqli_query($conn, "SELECT is_admin FROM users WHERE id=$target_id");
    if(!$res || mysqli_num_rows($res) === 0){ die('User not found'); }
    $target = mysqli_fetch_assoc($res);
    $target_level = (int)$target['is_admin'];

    // Rank 1 cannot edit other admins
    if($my_level === 1 && $target_level >= 1 && $target_id !== $my_id){
        die('Only Super Admins can edit other admin accounts.');
    }

    mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email' WHERE id=$target_id");
    header('Location: admin_manage_users.php?updated=1');
    exit;
}

// Display form
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = mysqli_query($conn, "SELECT id, full_name, email, is_admin FROM users WHERE id=$id");
if(!$res || mysqli_num_rows($res) === 0){ die('User not found'); }
$user = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>body{font-family:Arial, sans-serif;padding:30px;background:#f8fafc} .box{background:#fff;padding:20px;border-radius:8px;max-width:560px;margin:auto;}</style>
</head>
<body>
    <div class="box">
        <h2>Edit User</h2>
        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
            <div>
                <label>Full Name</label><br>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required style="width:100%;padding:8px;margin:6px 0;">
            </div>
            <div>
                <label>Email</label><br>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required style="width:100%;padding:8px;margin:6px 0;">
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button type="submit" style="background:#10b981;color:#fff;border:none;padding:10px 14px;border-radius:6px;">Save</button>
                <a href="admin_manage_users.php" style="background:#6b7280;color:#fff;padding:10px 14px;border-radius:6px;text-decoration:none;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>