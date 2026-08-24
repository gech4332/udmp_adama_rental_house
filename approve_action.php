<?php
include('db.php');
if(isset($_POST['approve'])) {
    $id = $_POST['house_id'];
    // Approve the house: set status to 'Available' and is_approved to 1
    mysqli_query($conn, "UPDATE houses SET status = 'Available', is_approved = 1 WHERE id = '$id'");
    header("Location: admin_manage_houses.php?msg=Approved");
}

if(isset($_POST['reject'])) {
    $id = $_POST['house_id'];
    mysqli_query($conn, "DELETE FROM houses WHERE id = '$id'");
    header("Location: admin_manage_houses.php?msg=Rejected");
}
?>