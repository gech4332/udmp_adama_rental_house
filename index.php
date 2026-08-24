<?php 
// This MUST be the very first line of the file!
session_start();
include('db.php'); 

// Cache control
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Rent - Adama</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }
        html { min-height: 100%; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: #f4f4f4;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        body > .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 80px);
        }

        /* Consistent Top Navigation */
        .top-nav {
            background: #4b7b8a;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .top-nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }
        .top-nav a:hover { color: #ffc107; }
        .top-nav i { font-size: 18px; }

        /* Page Header Title */
        .page-title {
            padding: 18px 24px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            background: #4b7b8a;
        }
        .page-title h1 { margin: 0; color: white; font-size: 24px; }

        .search-area {
            background: white;
            padding: 18px;
            margin: 18px auto 0;
            max-width: 1240px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-area form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            align-items: center;
        }
        .search-area input,
        .search-area select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 180px;
            flex: 1 1 180px;
        }
        .search-area button {
            padding: 12px 22px;
            background: #ffc107;
            color: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .search-area button:hover { background: #e0a800; }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            margin: 0 auto;
            width: 100%;
            max-width: 1240px;
            flex: 1;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 100%;
        }
        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #eee;
        }

        .badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            z-index: 10;
            text-transform: capitalize;
        }
        .available { background-color: #28a745; }
        .rented { background-color: #dc3545; }

        .category-label {
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

        .card-content { padding: 18px; flex-grow: 1; }
        .price { color: #28a745; font-size: 20px; font-weight: bold; }

        .phone-hidden {
            display: inline-block;
            width: 100%;
            padding: 10px;
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px dashed #ced4da;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }
        .phone-hidden::before { content: "🔒 "; font-size: 12px; }

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
            background: rgba(75, 123, 138, 0.9);
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
    <div class="page-wrapper">
        <nav class="top-nav">
            <a href="Home.php" title="Home">
                <i class="fa-solid fa-home"></i>
            </a>
            <div class="nav-right">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="manage_houses.php" style="margin-right: 15px;">Manage Posts</a>
                    <a href="logout.php" style="color:#ffc107;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-user-circle"></i> Landlord Login</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="page-title">
            <h1>Adama House Rental Search</h1>
        </div>

    <div class="search-area">
        <form method="GET" action="index.php">
            <select name="cat">
                <option value="">All Categories</option>
                <optgroup label="Residential">
                    <option value="Single Home" <?php if(isset($_GET['cat']) && $_GET['cat'] == 'Single Home') echo 'selected'; ?>>Single Home</option>
                    <option value="Apartment" <?php if(isset($_GET['cat']) && $_GET['cat'] == 'Apartment') echo 'selected'; ?>>Apartment</option>
                    <option value="Villa" <?php if(isset($_GET['cat']) && $_GET['cat'] == 'Villa') echo 'selected'; ?>>Villa</option>
                </optgroup>
                <optgroup label="Commercial">
                    <option value="Office" <?php if(isset($_GET['cat']) && $_GET['cat'] == 'Office') echo 'selected'; ?>>Office</option>
                    <option value="Shop" <?php if(isset($_GET['cat']) && $_GET['cat'] == 'Shop') echo 'selected'; ?>>Shop</option>
                    <option value="Warehouse" <?php if(isset($_GET['cat']) && $_GET['cat'] == 'Warehouse') echo 'selected'; ?>>Warehouse</option>
                </optgroup>
            </select>
            <input type="text" name="kb" placeholder="Search Kebele..." value="<?php echo isset($_GET['kb']) ? htmlspecialchars($_GET['kb']) : ''; ?>">
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
        // Show approved listings, including those marked as rented by the landlord.
        $sql = "SELECT houses.*, users.full_name FROM houses 
                LEFT JOIN users ON houses.user_id = users.id 
                WHERE houses.status IN ('Available', 'Rented') AND houses.is_approved = 1";

        // 1. Filter by Specific Category
        if(!empty($_GET['cat'])) {
            $c = mysqli_real_escape_string($conn, $_GET['cat']);
            $sql .= " AND category = '$c'"; 
        }

        // 2. Filter by Kebele
        if(!empty($_GET['kb'])) { 
            $k = mysqli_real_escape_string($conn, $_GET['kb']); 
            $sql .= " AND kebele LIKE '%$k%'"; 
        }

        // 3. Filter by Max Price
        if(!empty($_GET['max_pr'])) { 
            $max = (int)$_GET['max_pr']; 
            $sql .= " AND amount <= $max"; 
        }

        // 4. Sorting Logic (FIXED)
        $sort = $_GET['sort'] ?? 'newest';
        if($sort == 'price_low') {
            $sql .= " ORDER BY amount ASC";
        } elseif($sort == 'price_high') {
            $sql .= " ORDER BY amount DESC";
        } else {
            $sql .= " ORDER BY created_at DESC";
        }

        $res = mysqli_query($conn, $sql);

        if(mysqli_num_rows($res) > 0) {
            while($row = mysqli_fetch_assoc($res)) { 
                $status = $row['status'] ?? 'Available';
                $badgeClass = ($status == 'Rented') ? 'rented' : 'available'; 
        ?> 
            <div class="card">
                <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>

                <?php if(!empty($row['video_file']) && $status == 'Available'): ?>
                    <div class="video-wrapper" onclick="startVideo(this, 'uploads/<?php echo $row['video_file']; ?>')">
                        <div class="play-btn">▶ Watch Video</div>
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="House">
                    </div>
                <?php else: ?>
                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="House Image">
                <?php endif; ?>
                
                <div class="card-content">
                    <span class="category-label"><?php echo htmlspecialchars($row['category']); ?></span>
                    
                    <div class="price"><?php echo number_format($row['amount']); ?> ETB/month</div>
                    <h3 style="margin: 5px 0;">Kebele: <?php echo htmlspecialchars($row['kebele']); ?></h3>
                    
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($row['full_name'] ?? 'Private Landlord'); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['street']); ?></p>
                    <p style="font-size: 14px; color: #444;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    <p style="font-size: 12px; color: #888; margin-bottom: 10px;">📅 Posted on: <?php echo date("M d, Y", strtotime($row['created_at'])); ?> </p>

                    <?php if(!empty($row['map_link'])): ?>
                        <p><a href="<?php echo htmlspecialchars($row['map_link']); ?>" target="_blank" style="color:#4b7b8a; text-decoration:none; font-weight:bold;"><i class="fas fa-map-marker-alt"></i> View on Maps</a></p>
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
                </div>
            </div>
        <?php 
            } 
        } else {
            echo "<p style='text-align:center; grid-column: 1/-1; padding: 40px;'>No properties match your search.</p>";
        }
        ?>
    </div>

    <script>
    function startVideo(container, videoSrc) {
        container.innerHTML = `
            <video width="100%" height="200px" controls autoplay style="object-fit: cover;">
                <source src="${videoSrc}" type="video/mp4">
                Your browser does not support video.
            </video>
        `;
    }
    </script>

    </div>
    <?php include('footer.php'); ?>
</body>
</html>