<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) exit();

$id = (int)$_GET['id'];
$action = $_GET['action'];

if($action == 'approve') {
    // 1. Update Request to approved (status 1)
    mysqli_query($conn, "UPDATE requests SET status = 1 WHERE id = $id");
    
    // 2. Automatically set the house to Available (status 0) so it shows in "Active"
    mysqli_query($conn, "UPDATE houses SET status = 0 WHERE id = (SELECT house_id FROM requests WHERE id = $id)");
}

// Redirect back - the item will no longer be in the list because status is now 1
header("Location: admin_manage_requests.php");
exit();