<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) exit();

$id = (int)$_GET['id'];
$action = $_GET['action'];

if($action == 'approve') {
    // Approve request and mark house visible
    mysqli_query($conn, "UPDATE requests SET status = 1 WHERE id = $id");
    mysqli_query($conn, "UPDATE houses SET status = 'Available', is_approved = 1 WHERE id = (SELECT house_id FROM requests WHERE id = $id)");
} elseif($action == 'reject') {
    // Reject request and mark house as rejected (keeps record for audit)
    mysqli_query($conn, "UPDATE requests SET status = 2 WHERE id = $id");
    mysqli_query($conn, "UPDATE houses SET status = 'Rejected', is_approved = 0 WHERE id = (SELECT house_id FROM requests WHERE id = $id)");
} elseif($action == 'approve_house') {
    // Approve house directly (when no request record exists)
    mysqli_query($conn, "UPDATE houses SET status = 'Available', is_approved = 1 WHERE id = $id");
} elseif($action == 'reject_house') {
    // Reject house directly
    mysqli_query($conn, "DELETE FROM houses WHERE id = $id");
}

// Redirect back - the item will no longer be in the list because status is now 1
header("Location: admin_manage_requests.php");
exit();