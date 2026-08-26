<?php 
session_start();
include('db.php');

// Security: Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$house_id = (int)$_GET['id'];

// Fetch the current data for this house
$query = mysqli_query($conn, "SELECT * FROM houses WHERE id = $house_id AND user_id = $user_id");
$data = mysqli_fetch_assoc($query);

// If house doesn't exist or doesn't belong to this landlord, stop them
if(!$data){
    die("Error: Listing not found or you do not have permission to edit it.");
}

if(isset($_POST['update'])){
    $kebele = mysqli_real_escape_string($conn, $_POST['kebele']);
    $amount = (int)$_POST['amount'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);

    // Handle Image: Keep old one if new one isn't uploaded
    $imgName = $data['image']; 
    if(!empty($_FILES['house_image']['name'])){
        $imgName = time() . "_" . $_FILES['house_image']['name'];
        move_uploaded_file($_FILES['house_image']['tmp_name'], "uploads/" . $imgName);
    }

    // Update query
    $update_sql = "UPDATE houses SET kebele='$kebele', amount='$amount', phone='$phone', description='$desc', image='$imgName' 
                   WHERE id=$house_id AND user_id=$user_id";
    
    if(mysqli_query($conn, $update_sql)){
        echo "<script>alert('Update Successful!'); window.location='manage_houses.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit My House</title>
    <style>
        body { 
            font-family: Arial;
         background: #f4f4f4; 
         padding: 40px; 
        }
     .form-box {
        background: white;
        padding: 20px;
        max-width: 500px; 
        margin: auto;
        border-radius: 8px;
         box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
     input, textarea { 
        width: 100%;
        padding: 10px; 
        margin: 10px 0;
        border: 1px solid #ddd; 
        border-radius: 4px; 
        box-sizing: border-box; 
    }
        button {
             background: #007bff; 
             color: white; 
             border: none; 
             padding: 12px; 
             cursor: pointer; 
             width: 100%;
             border-radius: 4px; 
            }
        .cancel { 
            display:block;
            text-align:center; 
            margin-top:10px; 
            color: #666;
            text-decoration: none;
         }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Edit House Details</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Kebele</label>
            <input type="text" name="kebele" value="<?php echo htmlspecialchars($data['kebele']); ?>" required>
            
            <label>Monthly Price (ETB)</label>
            <input type="number" name="amount" value="<?php echo htmlspecialchars($data['amount']); ?>" required>

            <label>Phone Number</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($data['phone']); ?>" required>

            <label>Description</label>
            <textarea name="desc" rows="4"><?php echo htmlspecialchars($data['description']); ?></textarea>

            <label>Current Photo:</label><br>
            <img src="uploads/<?php echo $data['image']; ?>" width="100" style="margin-bottom: 10px;"><br>
            <label>Upload New Photo (Optional)</label>
            <input type="file" name="house_image">
            <button type="submit" name="update">Save Changes</button>
            <a href="manage_houses.php" class="cancel">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>