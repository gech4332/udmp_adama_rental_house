<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

// Only super admin can delete users
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 2){
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    csrf_validate();
    $target_id = (int)($_POST['user_id'] ?? 0);
    $my_id = (int)$_SESSION['user_id'];

    // Cannot delete yourself
    if($target_id === $my_id){
        header("Location: admin_manage_users.php?msg=no_self_delete");
        exit();
    }

    // Load the target user
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $target_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if($res && ($target = mysqli_fetch_assoc($res))){

        // Cannot delete another super admin (rank 2)
        if((int)$target['is_admin'] >= 2){
            header("Location: admin_manage_users.php?msg=no_super_delete");
            exit();
        }

        // Delete their house image files
        $stmt2 = mysqli_prepare($conn, "SELECT id, image, video_file FROM houses WHERE user_id=?");
        mysqli_stmt_bind_param($stmt2, "i", $target_id);
        mysqli_stmt_execute($stmt2);
        $houses = mysqli_stmt_get_result($stmt2);
        $hids = [];
        if($houses){
            while($h = mysqli_fetch_assoc($houses)){
                $hids[] = (int)$h['id'];
                if(!empty($h['image']) && file_exists("uploads/" . $h['image'])){
                    @unlink("uploads/" . $h['image']);
                }
                if(!empty($h['video_file']) && file_exists("uploads/" . $h['video_file'])){
                    @unlink("uploads/" . $h['video_file']);
                }
            }
        }

        if(!empty($hids)){
            $placeholders = implode(',', array_fill(0, count($hids), '?'));
            $types = str_repeat('i', count($hids));

            $stmt3 = mysqli_prepare($conn, "SELECT filename FROM house_images WHERE house_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt3, $types, ...$hids);
            mysqli_stmt_execute($stmt3);
            $imgs = mysqli_stmt_get_result($stmt3);
            if($imgs){
                while($im = mysqli_fetch_assoc($imgs)){
                    if(!empty($im['filename']) && file_exists("uploads/" . $im['filename'])){
                        @unlink("uploads/" . $im['filename']);
                    }
                }
            }
            $stmt4 = mysqli_prepare($conn, "DELETE FROM house_images WHERE house_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt4, $types, ...$hids);
            mysqli_stmt_execute($stmt4);

            $stmt_ha = mysqli_prepare($conn, "DELETE FROM house_amenities WHERE house_id IN ($placeholders)");
            mysqli_stmt_bind_param($stmt_ha, $types, ...$hids);
            mysqli_stmt_execute($stmt_ha);
        }

        // Delete their houses and requests (cleanup)
        $del = mysqli_prepare($conn, "DELETE FROM requests WHERE user_id=?");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM requests WHERE house_id IN (SELECT id FROM houses WHERE user_id=?)");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM rental_requests WHERE house_id IN (SELECT id FROM houses WHERE user_id=?)");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM rental_requests WHERE user_id=?");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM houses WHERE user_id=?");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM notifications WHERE user_id=?");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM admin_invites WHERE user_id=?");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
        mysqli_stmt_bind_param($del, "i", $target_id);
        mysqli_stmt_execute($del);

        header("Location: admin_manage_users.php?msg=deleted");
        exit();
    }
}

// Fallback
header("Location: admin_manage_users.php");
exit();
