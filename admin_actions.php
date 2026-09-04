<?php
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

switch($action){
    case 'approve':
        // mark request as handled and approve the house
        mysqli_query($conn, "UPDATE houses SET status='1', is_approved=1 WHERE id=(SELECT house_id FROM requests WHERE id=$id)");
        mysqli_query($conn, "UPDATE requests SET status=1 WHERE id=$id");
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject':
        mysqli_query($conn, "UPDATE requests SET status=2 WHERE id=$id");
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    case 'approve_house':
        mysqli_query($conn, "UPDATE houses SET status='0', is_approved=1 WHERE id=$id");
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject_house':
        mysqli_query($conn, "UPDATE houses SET status='Rejected', is_approved=0 WHERE id=$id");
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    default:
        header("Location: admin_manage_requests.php");
}
exit();
