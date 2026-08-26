<?php
session_start(); //
include('db.php'); //

$house_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Check current status
$check = mysqli_query($conn, "SELECT status FROM houses WHERE id=$house_id AND user_id=$user_id");
$row = mysqli_fetch_assoc($check);

if($row) {
    // If it's Available, make it Rented. If it's Rented, make it Available.
    $new_status = ($row['status'] == 'Available') ? 'Rented' : 'Available';
    
    $update = mysqli_query($conn, "UPDATE houses SET status='$new_status' WHERE id=$house_id AND user_id=$user_id");
    
    if($update) {
        header("Location: manage_houses.php?msg=success");
        exit();
    } else {
        echo "Update failed: " . mysqli_error($conn); //
    }
}
?>