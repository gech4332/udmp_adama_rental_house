<?php 
// 1. Start session and include database connection
session_start();
include('db.php'); 

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
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 20px; }
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #333; text-align: center; margin-bottom: 5px; }
        .user-info { text-align: center; color: #666; font-size: 14px; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #555; }
        input, textarea, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; margin-top: 25px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #218838; }
        .nav-link { display: block; text-align: center; margin-top: 15px; color: #007bff; text-decoration: none; }
        .error-msg { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Post Your House</h2>
        <div class="user-info">
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Landlord'); ?></strong> | 
            <a href="logout.php" style="color:#dc3545;">Logout</a>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <label>Kebele</label>
            <input type="text" name="kebele" placeholder="e.g. 03 or 12" required>

            <label>Street & House Number</label>
            <input type="text" name="street" placeholder="Street name" required>
            <input type="text" name="house_num" placeholder="House # (Optional)" style="margin-top:5px;">

            <label>Property Category</label>
            <select name="category" required>
                <option value="">-- Select Type --</option>
                <optgroup label="Residential">
                    <option value="Single Home">Single Home</option>
                    <option value="Apartment">Apartment</option>
                    <option value="Villa">Villa</option>
                </optgroup>
                <optgroup label="Commercial">
                    <option value="Office">Office</option>
                    <option value="Shop">Shop</option>
                    <option value="Warehouse">Warehouse</option>
                </optgroup>
            </select>

            <label>Price per Month (ETB)</label>
            <input type="number" name="amount" placeholder="Amount in ETB" required>

            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="0911..." required>

            <label>Google Maps Link</label>
            <input type="url" name="map_link" placeholder="Paste link from Google Maps">

            <label>House Photo</label>
            <input type="file" name="house_image" accept="image/*" required>

            <label>Upload Video Tour (Optional)</label>
            <input type="file" name="house_video" accept="video/mp4,video/x-m4v,video/*">

            <label>Description</label>
            <textarea name="desc" rows="3" placeholder="Describe water, electricity, etc..."></textarea>

            <label>Secret Delete Key</label>
            <input type="password" name="delete_key" placeholder="Password to delete this later" required>

            <button type="submit" name="submit">Publish Listing</button>
        </form>
        
        <a href="index.php" class="nav-link">Go to Search Page</a>

        <?php
        if(isset($_POST['submit'])){
            // 1. Setup Folder and Permissions
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            // 2. Sanitize Inputs
            $kebele   = mysqli_real_escape_string($conn, $_POST['kebele']);
            $street   = mysqli_real_escape_string($conn, $_POST['street']);
            $h_num    = mysqli_real_escape_string($conn, $_POST['house_num']);
            $category = mysqli_real_escape_string($conn, $_POST['category']); // New
            $amount   = (int)$_POST['amount'];
            $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
            $map      = mysqli_real_escape_string($conn, $_POST['map_link']);
            $desc     = mysqli_real_escape_string($conn, $_POST['desc']);
            $key      = mysqli_real_escape_string($conn, $_POST['delete_key']);
            $user_id  = $_SESSION['user_id'];

            // 3. Handle Video Upload
            $videoName = "";
            if(!empty($_FILES['house_video']['name'])){
                $videoName = time() . "_v_" . basename($_FILES['house_video']['name']);
                move_uploaded_file($_FILES['house_video']['tmp_name'], "uploads/" . $videoName);
            }

            // 4. Handle Image Upload
            $imgName = time() . "_" . basename($_FILES['house_image']['name']);
            $target = "uploads/" . $imgName;

            if(move_uploaded_file($_FILES['house_image']['tmp_name'], $target)){
                // 5. Insert into Database (Added 'category')
                $sql = "INSERT INTO houses (kebele, street, house_number, category, amount, phone, map_link, image, description, delete_key, user_id, video_file) 
                        VALUES ('$kebele', '$street', '$h_num', '$category', '$amount', '$phone', '$map', '$imgName', '$desc', '$key', '$user_id', '$videoName')";
                
                if(mysqli_query($conn, $sql)){
                    echo "<script>alert('House posted successfully!'); window.location='index.php';</script>";
                } else {
                    echo "<div class='error-msg'>DB Error: " . mysqli_error($conn) . "</div>";
                }
            } else {
                echo "<div class='error-msg'>Upload Error: Please check 'uploads' folder permissions.</div>";
            }
        }
        ?>
    </div>
</body>
</html>