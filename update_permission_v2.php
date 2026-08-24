<?php
session_start();
include('db.php');

// Ensure caller is logged in and an admin
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    die("Access Denied");
}

$my_level = (int)$_SESSION['is_admin'];

// Enforce: Only Super Admin (level 2) may change user permissions
if($my_level !== 2){
    die("Only Super Admins may change permissions.");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $target_id = (int)$_POST['user_id'];
    $new_level = (int)$_POST['action'];

    // Get target's current level to prevent illegal demotions[cite: 5]
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_admin FROM users WHERE id=$target_id"));
    $target_current = $row['is_admin'];

    // Rank 1 cannot touch other Admins or promote to Rank 2[cite: 5]
    if($my_level == 1 && ($target_current >= 1 || $new_level == 2)){
        die("Security Alert: Only Super Admins can perform this action.");
    }

    $sql = "UPDATE users SET is_admin=$new_level WHERE id=$target_id";
    if(mysqli_query($conn, $sql)){
        header("Location: admin_manage_users.php?success=1");
    }
}