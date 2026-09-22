<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || isset($_POST['ajax']);
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? $isAjax ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');
$me = (int)$_SESSION['user_id'];

if($id <= 0 || !in_array($action, ['accept', 'reject', 'cancel'], true)){
    header("Location: manage_houses.php");
    exit();
}

csrf_validate();

// Tenant withdraws their own pending rental request
if($action === 'cancel'){
    $stmt = mysqli_prepare($conn, "
        SELECT rr.*, h.user_id AS house_owner, h.kebele
        FROM rental_requests rr
        JOIN houses h ON rr.house_id = h.id
        WHERE rr.id=? AND rr.user_id=? AND rr.status='pending'
    ");
    mysqli_stmt_bind_param($stmt, "ii", $id, $me);
    mysqli_stmt_execute($stmt);
    $rr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$rr){
        $resp = ['type' => 'info', 'message' => 'This rental request is no longer pending.', 'title' => 'No action', 'redirect' => 'index.php'];
    } else {
        $stmt2 = mysqli_prepare($conn, "UPDATE rental_requests SET status='cancelled' WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        $notif_msg = (($_SESSION['user_name'] ?? 'A tenant') . ' cancelled their request for the property in Kebele ' . $rr['kebele'] . '.');
        $house_owner = (int)$rr['house_owner'];
        $stmt3 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'info', 'Rental request cancelled', ?, 'manage_houses.php')");
        mysqli_stmt_bind_param($stmt3, "is", $house_owner, $notif_msg);
        mysqli_stmt_execute($stmt3);
        $resp = ['type' => 'success', 'message' => 'Your rental request was cancelled.', 'title' => 'Request cancelled', 'redirect' => 'house_detail.php?house=' . (int)$rr['house_id']];
    }
    if($isAjax){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resp);
        exit();
    }
    header("Location: " . $resp['redirect']);
    exit();
}

// Fetch the request joined with its house; ensure the current user owns the house
$stmt = mysqli_prepare($conn, "
    SELECT rr.*, h.kebele, h.user_id AS house_owner
    FROM rental_requests rr
    JOIN houses h ON rr.house_id = h.id
    WHERE rr.id=? AND h.user_id=?
");
mysqli_stmt_bind_param($stmt, "ii", $id, $me);
mysqli_stmt_execute($stmt);
$rr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$rr || $rr['status'] !== 'pending'){
    header("Location: manage_houses.php");
    exit();
}

$kebele = $rr['kebele'];
$tenant_id = (int)$rr['user_id'];
$house_id = (int)$rr['house_id'];

if($action === 'accept'){
    $stmt2 = mysqli_prepare($conn, "UPDATE rental_requests SET status='accepted' WHERE id=?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);

    $stmt3 = mysqli_prepare($conn, "UPDATE houses SET status='Rented' WHERE id=?");
    mysqli_stmt_bind_param($stmt3, "i", $house_id);
    mysqli_stmt_execute($stmt3);

    $stmt4 = mysqli_prepare($conn, "UPDATE rental_requests SET status='rejected' WHERE house_id=? AND id<>? AND status='pending'");
    mysqli_stmt_bind_param($stmt4, "ii", $house_id, $id);
    mysqli_stmt_execute($stmt4);

    $notif_msg = "Your request to rent the property in Kebele $kebele was accepted. Open the property to call the owner.";
    $notif_link = "house_detail.php?house=" . $house_id;
    $stmt5 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'rent_request', 'Rental request accepted', ?, ?)");
    mysqli_stmt_bind_param($stmt5, "iss", $tenant_id, $notif_msg, $notif_link);
    mysqli_stmt_execute($stmt5);
    header("Location: manage_houses.php?msg=accepted");
} else {
    $stmt2 = mysqli_prepare($conn, "UPDATE rental_requests SET status='rejected' WHERE id=?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);

    $notif_msg = "Your request to rent the property in Kebele $kebele was declined by the owner.";
    $stmt3 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'rejection', 'Rental request declined', ?, 'index.php')");
    mysqli_stmt_bind_param($stmt3, "is", $tenant_id, $notif_msg);
    mysqli_stmt_execute($stmt3);
    header("Location: manage_houses.php?msg=rejected");
}
exit();