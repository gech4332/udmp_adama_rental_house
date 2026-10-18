<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

// Only super admin can toggle roles
if(!admin_meets_level(2)){
    die("Access Denied");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    csrf_validate();
    $user_id = (int)$_POST['user_id'];

    if($user_id === (int)($_SESSION['user_id'] ?? 0)){
        header("Location: admin_manage_users.php?msg=no_self_revoke");
        exit();
    }

    // Read the target's REAL role from the database — never trust a role
    // value posted by the browser.
    $stmt = mysqli_prepare($conn, "SELECT is_admin FROM users WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $target = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    if(!$target){
        header("Location: admin_manage_users.php?msg=RoleUpdated");
        exit();
    }
    $current_status = (int)$target['is_admin'];

    // Super admins are managed only through the invite/revoke flow.
    if($current_status >= 2){
        header("Location: admin_manage_users.php?msg=no_revoke_super");
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