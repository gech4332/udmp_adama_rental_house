<?php 
session_start();
include('db.php'); //

// 1. Security: Only allow logged-in landlords
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Landlord Dashboard - Manage My Posts</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .header { background: #4b7b8a;; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px; margin-bottom: 30px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); position: relative; border: 1px solid #ddd; }
        .card img { width: 100%; height: 200px; object-fit: cover; }
        .card-info { padding: 20px; }
        
        /* Status Badges */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; position: absolute; top: 15px; left: 15px; }
        .available { background: #28a745; color: white; }
        .rented { background: #dc3545; color: white; }
        .pending { background: #f59e0b; color: white; }

        /* Buttons */
        .btn {
             display: block; 
             text-align: center; 
             text-decoration: none;
             padding: 10px;
             border-radius: 6px;
             margin-bottom: 8px; 
             font-weight: bold; 
             font-size: 14px; 
             transition: 0.3s;
             border: none; 
             width: 100%; 
             cursor: pointer;
         }
        .btn-status {
             background: #6c757d; 
             color: white;
             }
        .btn-edit { 
            background: #ffc107; 
            color: #333;
         }
        .btn-delete {
             background: #fee;
              color: #d93025;
               border: 1px solid #d93025; }
        .btn:hover {
             opacity: 0.8;
             }
                   .category-labelph {
            display: inline-block;
            background: #e9ecef;
            color: #4b7b8a;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h2 style="margin:0;">Wellcome: <?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
    </div>
    <nav>
        <a href="index.php" style="color: white; text-decoration: none; margin-right: 20px;">Public Site</a>
        <a href="post_house.php" style="background: #28a745; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none;">+ Post New House</a>
        <a href="logout.php" style="color: #ffc107; text-decoration: none; margin-left: 15px;">Logout</a>
    </nav>
</div>

<div class="grid">
    <?php
    // Fetch only houses belonging to this user
    $query = "SELECT * FROM houses WHERE user_id = '$current_user' ORDER BY id DESC";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            // Ensure status has a default value if NULL
            $status = $row['status'] ?? 'Available';
    ?>
        <div class="card">
            <span class="badge <?php 
                $badge_class = 'available';
                if(strcasecmp($status, 'Rented') === 0) $badge_class = 'rented';
                elseif(strcasecmp($status, 'Pending') === 0) $badge_class = 'pending';
                echo $badge_class;
            ?>">
                <?php echo htmlspecialchars($status); ?>
            </span>

            <img src="uploads/<?php echo $row['image']; ?>" alt="House Image">
            
            <div class="card-info">
                                    <span class="category-labelph"><?php echo htmlspecialchars($row['category']); ?></span>

                <h3 style="margin-top:0;">Kebele <?php echo htmlspecialchars($row['kebele']); ?></h3>
                <p>Price: <strong><?php echo number_format($row['amount']); ?> ETB</strong></p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

                <a href="toggle_status.php?id=<?php echo $row['id']; ?>" class="btn btn-status">
                    Mark as <?php echo ($status == 'Available') ? 'Rented' : 'Available'; ?>
                </a>

                <a href="edit_house.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                    Edit Details
                </a>

                <form action="delete.php" method="POST" onsubmit="return confirm('WARNING: This will permanently delete this listing. Continue?')">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="delete_btn" class="btn btn-delete">Delete Forever</button>
                </form>
            </div>
        </div>
    <?php 
        }
    } else {
        echo "<div style='grid-column: 1/-1; text-align:center; padding: 50px; background: white; border-radius: 8px;'>
                <h3>You haven't posted any listings yet.</h3>
                <a href='post_house.php' style='color: #007bff;'>Post your first house now!</a>
              </div>";
    }
    ?>
</div>

</body>
<?php include('footer.php'); ?>
</html>