<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) die("Denied");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    csrf_validate();
    $id = (int)$_POST['id'];
    $action = $_POST['action'];

    switch($action) {
        case 'approve_user':
            if($_SESSION['is_admin'] < 2) die("Denied");
            $stmt = mysqli_prepare($conn, "UPDATE users SET status = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            header("Location: admin_manage_users.php?msg=approved");
            break;
            
        case 'delete_user':
            if($_SESSION['is_admin'] < 2) die("Denied");
            if($id != $_SESSION['user_id']){
                $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
            }
            header("Location: admin_manage_users.php?msg=deleted");
            break;

        case 'approve_house':
            $stmt = mysqli_prepare($conn, "UPDATE houses SET status = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            header("Location: admin_manage_houses.php?msg=posted");
            break;

        case 'delete_house':
            $stmt = mysqli_prepare($conn, "SELECT image FROM houses WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $hd = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if($hd && !empty($hd['image']) && file_exists("uploads/" . $hd['image'])){
                @unlink("uploads/" . $hd['image']);
            }
            $stmt = mysqli_prepare($conn, "SELECT filename FROM house_images WHERE house_id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $imgs = mysqli_stmt_get_result($stmt);
            if($imgs){
                while($im = mysqli_fetch_assoc($imgs)){
                    if(!empty($im['filename']) && file_exists("uploads/" . $im['filename'])){
                        @unlink("uploads/" . $im['filename']);
                    }
                }
            }
            $del = mysqli_prepare($conn, "DELETE FROM requests WHERE house_id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);

            $del = mysqli_prepare($conn, "DELETE FROM rental_requests WHERE house_id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);

            $del = mysqli_prepare($conn, "DELETE FROM house_images WHERE house_id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);

            $del = mysqli_prepare($conn, "DELETE FROM house_amenities WHERE house_id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);

            $del = mysqli_prepare($conn, "DELETE FROM houses WHERE id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);
            header("Location: admin_manage_houses.php?msg=deleted");
            break;
    }
}
?>