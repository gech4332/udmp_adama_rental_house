<?php
include('db.php');
if(isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Change status to 'Available' so it now appears on the public site
    $query = "UPDATE houses SET status = 'Available' WHERE id = '$id'";
    
    if(mysqli_query($conn, $query)) {
        header("Location: admin_manage_houses.php?view=available");
    }
}
?>