<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header("Location: login.php");
    exit();
}

if(empty($_POST['action'])){
    header("Location: admin_manage_requests.php");
    exit();
}
csrf_validate();

$action = $_POST['action'];
$id = (int)($_POST['id'] ?? 0);

function notifyOwner($conn, $house_id, $title, $message){
    $stmt = mysqli_prepare($conn, "SELECT user_id, kebele FROM houses WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $house_id);
    mysqli_stmt_execute($stmt);
    $owner = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$owner) return;
    $owner_id = (int)$owner['user_id'];
    $stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'listing', ?, ?, 'manage_houses.php')");
    mysqli_stmt_bind_param($stmt2, "iss", $owner_id, $title, $message);
    mysqli_stmt_execute($stmt2);
}

switch($action){
    case 'approve':
        $stmt = mysqli_prepare($conn, "SELECT house_id FROM requests WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $hq = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $house_id = $hq ? (int)$hq['house_id'] : 0;
        if($house_id){
            $stmt2 = mysqli_prepare($conn, "UPDATE houses SET status='Available', is_approved=1 WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "i", $house_id);
            mysqli_stmt_execute($stmt2);
            notifyOwner($conn, $house_id, 'Listing approved', 'Your listing was approved and is now live on the marketplace.');
        }
        $stmt3 = mysqli_prepare($conn, "UPDATE requests SET status=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt3, "i", $id);
        mysqli_stmt_execute($stmt3);
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject':
        $stmt = mysqli_prepare($conn, "SELECT house_id FROM requests WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $hq = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $house_id = $hq ? (int)$hq['house_id'] : 0;
        if($house_id){
            $stmt2 = mysqli_prepare($conn, "UPDATE houses SET status='Rejected' WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "i", $house_id);
            mysqli_stmt_execute($stmt2);
            notifyOwner($conn, $house_id, 'Listing rejected', 'Your listing was rejected. Please review and resubmit.');
        }
        $stmt3 = mysqli_prepare($conn, "UPDATE requests SET status=2 WHERE id=?");
        mysqli_stmt_bind_param($stmt3, "i", $id);
        mysqli_stmt_execute($stmt3);
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    case 'approve_house':
        $stmt = mysqli_prepare($conn, "UPDATE houses SET status='Available', is_approved=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $stmt_r = mysqli_prepare($conn, "UPDATE requests SET status=1 WHERE house_id=? AND status=0");
        mysqli_stmt_bind_param($stmt_r, "i", $id);
        mysqli_stmt_execute($stmt_r);
        notifyOwner($conn, $id, 'Listing approved', 'Your listing was approved and is now live on the marketplace.');
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject_house':
        $stmt = mysqli_prepare($conn, "UPDATE houses SET status='Rejected', is_approved=0 WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $stmt_r = mysqli_prepare($conn, "UPDATE requests SET status=2 WHERE house_id=? AND status=0");
        mysqli_stmt_bind_param($stmt_r, "i", $id);
        mysqli_stmt_execute($stmt_r);
        notifyOwner($conn, $id, 'Listing rejected', 'Your listing was rejected. Please review and resubmit.');
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    case 'approve_review':
        $stmt = mysqli_prepare($conn, "SELECT type FROM requests WHERE house_id=? AND status=0 ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $rt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $was_edit = ($rt && strtolower($rt['type']) === 'edit');
        $stmt2 = mysqli_prepare($conn, "UPDATE houses SET status='Available', is_approved=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        $stmt3 = mysqli_prepare($conn, "UPDATE requests SET status=1 WHERE house_id=? AND status=0");
        mysqli_stmt_bind_param($stmt3, "i", $id);
        mysqli_stmt_execute($stmt3);
        notifyOwner($conn, $id, 'Listing approved', $was_edit
            ? 'Your updated listing was approved and is now live on the marketplace again.'
            : 'Your listing was approved and is now live on the marketplace.');
        header("Location: admin_manage_requests.php?msg=approved");
        break;

    case 'reject_review':
        $stmt = mysqli_prepare($conn, "SELECT type FROM requests WHERE house_id=? AND status=0 ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $rt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $was_edit = ($rt && strtolower($rt['type']) === 'edit');
        $stmt2 = mysqli_prepare($conn, "UPDATE houses SET status='Rejected', is_approved=0 WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        $stmt3 = mysqli_prepare($conn, "UPDATE requests SET status=2 WHERE house_id=? AND status=0");
        mysqli_stmt_bind_param($stmt3, "i", $id);
        mysqli_stmt_execute($stmt3);
        notifyOwner($conn, $id, 'Listing rejected', $was_edit
            ? 'Your updated listing was rejected. Please review the comments and resubmit.'
            : 'Your listing was rejected. Please review and resubmit.');
        header("Location: admin_manage_requests.php?msg=rejected");
        break;

    default:
        header("Location: admin_manage_requests.php");
}
exit();