<?php
session_start();
include('db.php');
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) exit();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id = (int)$_POST['id'];
    mysqli_query($conn, "DELETE FROM houses WHERE id = $id");
    header("Location: admin_manage_houses.php?deleted");
}
?>