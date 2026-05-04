<?php
session_start();
include('db.php');
$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM users WHERE id = $id AND is_admin = 1");
header("Location: admin_manage_users.php");