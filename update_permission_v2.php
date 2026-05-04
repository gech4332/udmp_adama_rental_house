<?php
session_start();
include('db.php');

$my_level = $_SESSION['is_admin'];

// Security: Must be at least Rank 1 to be here[cite: 5]
if($my_level < 1) { die("Access Denied"); }

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