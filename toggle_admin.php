<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

// Only super admin can toggle roles
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 2){
    die("Access Denied");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    csrf_validate();
    $user_id = (int)$_POST['user_id'];
    $current_status = (int)$_POST['current_status'];

    if($user_id === (int)($_SESSION['user_id'] ?? 0)){
        header("Location: admin_manage_users.php?msg=no_self_revoke");
        exit();
    }

    // If they were 0 (user), make them 1 (admin). If they were 1, make them 0.
    $new_status = ($current_status == 1) ? 0 : 1;

    $sql_stmt = mysqli_prepare($conn, "UPDATE users SET is_admin = ? WHERE id = ?");
    mysqli_stmt_bind_param($sql_stmt, "ii", $new_status, $user_id);
    
    if(mysqli_stmt_execute($sql_stmt)){
        header("Location: admin_manage_users.php?msg=RoleUpdated");
    } else {
        echo "Error updating record.";
    }
}