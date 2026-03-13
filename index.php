<?php 
// This MUST be the very first line of the file!
session_start();
include('db.php'); 

// Tell the browser: "Don't show the old logged-in version, ask the server for a new one"
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Find Rent - Adama</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
        header { background: #333; color: white; padding: 20px; text-align: center; }
        .search-area { background: white; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .search-area form { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; align-items: center; }
        .search-area input, .search-area select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .search-area button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        
        .container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; padding: 20px; }
        
        /* Card and Badge Styles */
        .card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; flex-direction: column; position: relative; }
        .card img { width: 100%; height: 200px; object-fit: cover; background: #eee; }
        
        .badge {
            position: absolute; top: 15px; left: 15px;
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;
            color: white; z-index: 10; text-transform: capitalize;
        }
        .available { background-color: #28a745; } /* Green badge */
        .rented { background-color: #dc3545; }    /* Red badge */

        .card-content { padding: 15px; flex-grow: 1; }
        .price { color: #28a745; font-size: 20px; font-weight: bold; }
       
             /* Styling for the hidden phone message */
.phone-hidden {
    display: inline-block;
    width: 100%;
    padding: 10px;
    background-color: #f8f9fa; /* Light grey background */
    color: #6c757d;           /* Muted grey text */
    border: 1px dashed #ced4da; /* Dashed border to show it's "locked" */
    border-radius: 6px;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    margin-top: 5px;
}

/* Optional: Add an icon effect using emojis or characters */
.phone-hidden::before {
    content: "🔒 ";
    font-size: 12px;
}
.video-wrapper {
    position: relative;
    cursor: pointer;
    width: 100%;
    height: 200px;
    background: #000;
}
.play-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 123, 255, 0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: bold;
    pointer-events: none;
    z-index: 5;
}
    </style>
</head>
<body>
    <header>
        <h1>Adama House Rental Search</h1>
        <nav style="margin-top: 10px;">
            <a href="index.php" style="color:white; margin: 0 10px; text-decoration:none;">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="manage_houses.php" style="color:cyan; margin: 0 10px; text-decoration:none;">Manage My Posts</a>
                <a href="logout.php" style="color:#ff6666; margin: 0 10px; text-decoration:none;">Logout (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a>
            <?php else: ?>
                <a href="login.php" style="color:yellow; margin: 0 10px; text-decoration:none;">Landlord Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="search-area">
        <form method="GET" action="index.php">
            <input type="text" name="kb" placeholder="Search Kebele..." value="<?php echo isset($_GET['kb']) ? htmlspecialchars($_GET['kb']) : ''; ?>">
            <input type="number" name="min_pr" placeholder="Min Price" value="<?php echo isset($_GET['min_pr']) ? htmlspecialchars($_GET['min_pr']) : ''; ?>">
            <input type="number" name="max_pr" placeholder="Max Price" value="<?php echo isset($_GET['max_pr']) ? htmlspecialchars($_GET['max_pr']) : ''; ?>">
            
            <select name="sort">
                <option value="newest" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'newest') echo 'selected'; ?>>Newest First</option>
                <option value="price_low" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'price_low') echo 'selected'; ?>>Price: Low to High</option>
                <option value="price_high" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'price_high') echo 'selected'; ?>>Price: High to Low</option>
            </select>

            <button type="submit">Filter Houses</button>
            <a href="index.php" style="margin-left:10px; color: #666; text-decoration: none; font-size: 14px;">Reset</a>
        </form>
    </div>

    <div class="container">
        <?php
        // 1. Build SQL with JOIN to get owner name
        $sql = "SELECT houses.*, users.full_name FROM houses 
                LEFT JOIN users ON houses.user_id = users.id 
                WHERE 1=1";

        if(!empty($_GET['kb'])) { 
            $k = mysqli_real_escape_string($conn, $_GET['kb']); 
            $sql .= " AND kebele LIKE '%$k%'"; 
        }

        if(isset($_GET['min_pr']) && $_GET['min_pr'] !== '') { 
            $min = (int)$_GET['min_pr']; 
            $sql .= " AND amount >= $min"; 
        }

        if(isset($_GET['max_pr']) && $_GET['max_pr'] !== '') { 
            $max = (int)$_GET['max_pr']; 
            $sql .= " AND amount <= $max"; 
        }

        // 2. Sorting Logic
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
        if($sort == 'price_low') $sql .= " ORDER BY amount ASC";
        elseif($sort == 'price_high') $sql .= " ORDER BY amount DESC";
        else $sql .= " ORDER BY created_at DESC";

        $res = mysqli_query($conn, $sql); //

        // 3. Display Results Loop
        if(mysqli_num_rows($res) > 0) {
            while($row = mysqli_fetch_assoc($res)) { 
                $status = $row['status'] ?? 'Available'; // Default status
                $badgeClass = ($status == 'Rented') ? 'rented' : 'available'; 
        ?> 
            <div class="card">
               <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>

    <?php if(!empty($row['video_file']) && $status == 'Available'): ?>
        <div class="video-wrapper" onclick="startVideo(this, 'uploads/<?php echo $row['video_file']; ?>')">
            <div class="play-btn">▶ Watch Video</div>
            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="House" style="width:100%; height:100%; object-fit:cover;">
        </div>
    <?php else: ?>
        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="House Image">
    <?php endif; ?>
                
                <div class="card-content">
                    <div class="price"><?php echo number_format($row['amount']); ?> ETB/month</div>
                    <h3 style="margin: 10px 0;">Kebele: <?php echo htmlspecialchars($row['kebele']); ?></h3>
                    
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($row['full_name'] ?? 'Private Landlord'); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['street']); ?></p>
                    <p><strong>House No:</strong> <?php echo htmlspecialchars($row['house_number']); ?></p>
                    <p style="font-size: 14px; color: #444;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    <p style="font-size: 12px; color: #888; margin-bottom: 10px;">📅 Posted on: <?php echo date("M d, Y", strtotime($row['created_at'])); ?> </p>

                    <?php if(!empty($row['map_link'])): ?>
                        <p><a href="<?php echo htmlspecialchars($row['map_link']); ?>" target="_blank" style="color:#007bff; text-decoration:none; font-weight:bold;"> View on Maps</a></p>
                    <?php endif; ?>

                    <p style="margin-top: 15px;"><strong>📞 Contact Landlord:</strong><br>
                        <?php if ($status == 'Available'): ?>
                            <a href="tel:<?php echo $row['phone']; ?>" style="color: #28a745; font-size: 18px; font-weight: bold; text-decoration: none;">
                                <?php echo htmlspecialchars($row['phone']); ?>
                            </a>
                        <?php else: ?>
                            <span class="phone-hidden">Number Hidden (Already Rented)</span>
                        <?php endif; ?>
                    </p>

                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']): ?>
                        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #eee; text-align:center;">
                            <a href="manage_houses.php" style="color: #666; font-size: 12px; text-decoration: none;">Manage this listing</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php 
            } 
        } else {
            echo "<p style='text-align:center; grid-column: 1/-1; padding: 40px;'>No houses match your search.</p>";
        }
        ?>
    </div>
    <script>
function startVideo(container, videoSrc) {
    // Replace the image and play button with a video element
    container.innerHTML = `
        <video width="100%" height="100%" controls autoplay style="object-fit: cover;">
            <source src="${videoSrc}" type="video/mp4">
            Your browser does not support video.
        </video>
    `;
}
</script>
</body>
</html>