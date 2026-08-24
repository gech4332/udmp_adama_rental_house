<?php
session_start();
include('db.php');
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) exit();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = (int)$_POST['id'];
    $current_status = $_POST['current'];
    $new_status = ($current_status == 'Available' || $current_status == '0') ? 'Rented' : 'Available';
    
    mysqli_query($conn, "UPDATE houses SET status = '$new_status' WHERE id = $id");
    header("Location: admin_manage_houses.php");
}
?>