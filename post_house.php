<?php 
// 1. Start session and include database connection
session_start();
include('db.php'); //

// 2. Security: Redirect to login if the landlord is not signed in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post a House - Ethiopia Rentals</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 20px; }
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #333; text-align: center; margin-bottom: 5px; }
        .user-info { text-align: center; color: #666; font-size: 14px; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; color: #555; }
        input, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; margin-top: 20px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        .nav-link { display: block; text-align: center; margin-top: 15px; color: #007bff; text-decoration: none; }
        .logout-link { color: #dc3545; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Post Your House</h2>
        <div class="user-info">
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> | 
            <a href="logout.php" class="logout-link">Logout</a>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <label>Kebele</label>
            <input type="text" name="kebele" placeholder="e.g. 03 or 12" required> <label>Street & House Number</label>
            <input type="text" name="street" placeholder="Street name" required> <input type="text" name="house_num" placeholder="House # (Optional)"> <label>Price per Month (Birr)</label>
            <input type="number" name="amount" placeholder="Amount in ETB" required> <label>Phone Number</label>
            <input type="text" name="phone" placeholder="0911..." required> <label>Google Maps Link</label>
            <input type="url" name="map_link" placeholder="Paste Google Maps URL here"> <label>House Photo</label>
            <input type="file" name="house_image" accept="image/*" required> <label>Description</label>
            <textarea name="desc" rows="3" placeholder="Describe the rooms, water, electricity..."></textarea> <label>Secret Delete Key</label>
            <input type="password" name="delete_key" placeholder="Create a password to delete this later" required> <button type="submit" name="submit">Publish Listing</button>
       <label>Upload Video Tour (Optional)</label>
<input type="file" name="house_video" accept="video/mp4,video/x-m4v,video/*">
        </form>
        <a href="index.php" class="nav-link">Go to Search Page</a> <?php
        if(isset($_POST['submit'])){
        
$videoName = "";
if(!empty($_FILES['house_video']['name'])){
    $videoName = time() . "_video_" . basename($_FILES['house_video']['name']);
    move_uploaded_file($_FILES['house_video']['tmp_name'], "uploads/" . $videoName);
}
            // 3. Define and sanitize variables inside the submit block
            $kebele = mysqli_real_escape_string($conn, $_POST['kebele']);
            $street = mysqli_real_escape_string($conn, $_POST['street']);
            $house_num = mysqli_real_escape_string($conn, $_POST['house_num']);
            $amount = (int)$_POST['amount'];
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $map = mysqli_real_escape_string($conn, $_POST['map_link']);
            $desc = mysqli_real_escape_string($conn, $_POST['desc']);
            $key = mysqli_real_escape_string($conn, $_POST['delete_key']);
            $user_id = $_SESSION['user_id']; // Get the ID from the session

            // 4. Handle Image Upload
            $imgName = time() . "_" . basename($_FILES['house_image']['name']);
            $target = "uploads/" . $imgName;

            // Ensure the uploads directory exists
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if(move_uploaded_file($_FILES['house_image']['tmp_name'], $target)){
                // 5. Execute SQL with user_id
                
$sql = "INSERT INTO houses (kebele, street, house_number, amount, phone, map_link, image, description, delete_key, user_id, video_file) 
        VALUES ('$kebele', '$street', '$house_num', '$amount', '$phone', '$map', '$imgName', '$desc', '$key', '$user_id', '$videoName')";
                if(mysqli_query($conn, $sql)){
                    echo "<p style='color:green; text-align:center; font-weight:bold; margin-top:10px;'>Listing posted successfully!</p>";
                } else {
                    echo "<p style='color:red;'>Database Error: " . mysqli_error($conn) . "</p>";
                }
            } else {
                echo "<p style='color:red;'>Error: Failed to upload image. Please check folder permissions.</p>";
            }
        }
        ?>
    </div>
</body>
</html>