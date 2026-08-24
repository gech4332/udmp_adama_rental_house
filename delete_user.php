<?php
session_start();
include('db.php');

// Only allow POST from logged-in admins
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    die('Access Denied');
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: admin_manage_users.php');
    exit;
}

$my_level = (int)($_SESSION['is_admin'] ?? 0);
$my_id = (int)($_SESSION['user_id'] ?? 0);
$target_id = (int)($_POST['user_id']);

if($target_id === $my_id){
    die('Cannot delete your own account.');
}

// Fetch target level
$res = mysqli_query($conn, "SELECT is_admin FROM users WHERE id=$target_id");
if(!$res || mysqli_num_rows($res) === 0){
    header('Location: admin_manage_users.php');
    exit;
}
$row = mysqli_fetch_assoc($res);
$target_level = (int)$row['is_admin'];

// Only Super Admin can delete admin accounts
if($target_level >= 1 && $my_level < 2){
    die('Only Super Admins can delete admin accounts.');
}

mysqli_query($conn, "DELETE FROM users WHERE id=$target_id");
header('Location: admin_manage_users.php?deleted=1');
exit;