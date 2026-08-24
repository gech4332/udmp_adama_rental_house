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
        .required::after { content: ' *'; color: #dc3545; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; margin-top: 25px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #218838; }
        .nav-link { display: block; text-align: center; margin-top: 15px; color: #007bff; text-decoration: none; }
        .error-msg { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px; border: 1px solid #f5c6cb; }
        .auto-generated { background-color: #e8f5e9; color: #2e7d32; font-size: 12px; margin-top: 5px; padding: 5px 8px; border-radius: 3px; display: none; }
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
            <label class="required">Kebele</label>
            <input type="text" name="kebele" placeholder="e.g. 03 or 12" required>

            <label class="required">Street & House Number</label>
            <input type="text" name="street" placeholder="Street name" required>
            <input type="text" name="house_num" placeholder="House # (Optional)" style="margin-top:5px;">

            <label class="required">Property Category</label>
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

            <label class="required">Price per Month (ETB)</label>
            <input type="number" name="amount" placeholder="Amount in ETB" required>

            <label class="required">Phone Number</label>
            <input type="text" name="phone" placeholder="0911..." required>

            <label>Google Maps Link (Auto-Generated)</label>
            <input type="url" id="map_link" name="map_link" placeholder="Auto-generated from location..." readonly>
            <div class="auto-generated" id="auto-gen-note">✓ Auto-generated from location</div>

            <label class="required">House Photo</label>
            <input type="file" name="house_image" accept="image/*" required>

            <label>Upload Video Tour (Optional)</label>
            <input type="file" name="house_video" accept="video/mp4,video/x-m4v,video/*">

            <label>Description</label>
            <textarea name="desc" rows="3" placeholder="Describe water, electricity, etc..."></textarea>

            <button type="submit" name="submit">Publish Listing</button>
        </form>

        <script>
            // Auto-generate Google Maps link from location fields
            const kebeleInput = document.querySelector('input[name="kebele"]');
            const streetInput = document.querySelector('input[name="street"]');
            const houseNumInput = document.querySelector('input[name="house_num"]');
            const mapLinkInput = document.getElementById('map_link');
            const autoGenNote = document.getElementById('auto-gen-note');

            function updateMapLink() {
                const kebele = kebeleInput.value.trim();
                const street = streetInput.value.trim();
                const houseNum = houseNumInput.value.trim();

                if (kebele || street) {
                    // Build location string: "Kebele, Street, House#, Adama, Ethiopia"
                    let locationParts = [];
                    if (kebele) locationParts.push('Kebele ' + kebele);
                    if (street) locationParts.push(street);
                    if (houseNum) locationParts.push('House ' + houseNum);
                    locationParts.push('Adama');
                    locationParts.push('Ethiopia');

                    const location = locationParts.join(', ');
                    const mapsUrl = 'https://www.google.com/maps/search/' + encodeURIComponent(location);
                    
                    mapLinkInput.value = mapsUrl;
                    autoGenNote.style.display = 'block';
                } else {
                    mapLinkInput.value = '';
                    autoGenNote.style.display = 'none';
                }
            }

            // Update map link when location fields change
            kebeleInput.addEventListener('input', updateMapLink);
            streetInput.addEventListener('input', updateMapLink);
            houseNumInput.addEventListener('input', updateMapLink);
        </script>
        
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
                // Mark the house as pending and not approved - admin must approve
                $sql = "INSERT INTO houses (kebele, street, house_number, category, amount, phone, map_link, image, description, user_id, video_file, status, is_approved, created_at) 
                        VALUES ('$kebele', '$street', '$h_num', '$category', '$amount', '$phone', '$map', '$imgName', '$desc', $user_id, '$videoName', 'Pending', 0, NOW())";
                
                if(mysqli_query($conn, $sql)){
                    $house_id = mysqli_insert_id($conn);

                    // Create an admin request record so admins can approve this listing
                    $req_sql = "INSERT INTO requests (user_id, house_id, status, created_at) VALUES ($user_id, $house_id, 0, NOW())";
                    mysqli_query($conn, $req_sql);

                    // Inform the user that their listing is pending approval
                    echo "<script>alert('House submitted and is pending admin approval. You will see it listed as Pending in Manage Posts.'); window.location='manage_houses.php';</script>";
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