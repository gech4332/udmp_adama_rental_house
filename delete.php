<?php
include('db.php');
session_start(); //

// 1. Check if the user is logged in
if(!isset($_SESSION['user_id'])){
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

if(isset($_POST['delete_btn'])){
    $id = (int)$_POST['id']; // Cast to integer for security
    $input_key = mysqli_real_escape_string($conn, $_POST['key']);
    $current_user = $_SESSION['user_id'];

    // 2. Find the record AND ensure it belongs to the logged-in user
    $query = mysqli_query($conn, "SELECT image, delete_key FROM houses WHERE id = $id AND user_id = $current_user");
    
    if(mysqli_num_rows($query) > 0){
        $data = mysqli_fetch_assoc($query);

        // 3. Verify the secret key
        if($data['delete_key'] === $input_key){
            // Delete the physical image file
            if(file_exists("uploads/" . $data['image'])){
                unlink("uploads/" . $data['image']); //
            }
            
            // Delete from database
            mysqli_query($conn, "DELETE FROM houses WHERE id = $id");
            echo "<script>alert('Post Removed successfully'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Incorrect Secret Key!'); window.location='index.php';</script>";
        }
    } else {
        // This triggers if the ID doesn't exist OR it belongs to a different landlord
        echo "<script>alert('Unauthorized! You can only delete your own posts.'); window.location='index.php';</script>";
    }
}
?>