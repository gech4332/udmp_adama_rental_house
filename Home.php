<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adama Property Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .dropdown { position: relative; display: inline-block; }
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #ffffff;
        min-width: 160px;
        box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
        z-index: 100;
        border-radius: 8px;
    }
    .dropdown-content a {
        color: #333 !important;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-size: 14px;
    }
    .dropdown-content a:hover { background-color: #f1f1f1; border-radius: 8px; }
    .dropdown:hover .dropdown-content { display: block; }
        /* 1. Make the Hero the main container for everything at the top */
        .hero { 
            position: relative; 
            height: 500px; /* Increased height to accommodate the nav */
            background: #000; 
            color: white; 
            text-align: center; 
        }
        
        /* 2. The background image covers the whole hero including the nav area */
        .hero img.bg-image { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            opacity: 0.6; 
            position: absolute; 
            top: 0; 
            left: 0; 
            z-index: 0;
        }

        /* 3. Transparent Navigation overlaid on the image */
        .top-nav { 
            position: relative; /* Sits above the absolute image */
            z-index: 10;
            padding: 20px 40px; 
            display: flex; 
            justify-content: flex-end; 
            align-items: center;
            background: transparent; /* Removed header color */

        
        }

        .top-nav a { 
            
            float:right;
            color: white; 
            text-decoration: none; 
            font-size: 18px; 
            font-weight: bold; 
            transition: 0.3s;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.5); /* Helps visibility on image */
        }
        
        .top-nav a:hover { color: #ffc107; }

        .hero-content { 
            position: relative; 
            padding-top: 80px; 
            z-index: 1;
        }
        
        .btn { 
            background: #ffc107; 
            padding: 12px 30px; 
            color: black; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold;
            display: inline-block;
            transition: 0.3s;
        }
        .btn:hover { background: #e0a800; transform: scale(1.05); }

        /* Categories Section */
        .categories { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            padding: 50px 20px; 
            background: #f9f9f9; 
            flex-wrap: wrap; 
        }

        .cat-card { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            width: 250px; 
            text-align: center; 
            transition: 0.3s; 
        }
        .cat-card:hover { transform: translateY(-10px); }
        .cat-card i { font-size: 40px; color: #4b7b8a; margin-bottom: 15px; }
        
        footer { background: #4b7b8a; color: white; padding: 30px; text-align: center; }
   </style>
</head>
<body>

    <?php
        $hero_title = "A Unified Digital Marketplace for Adama Rent House";
        $hero_desc  = "Find Simple Homes, Luxury Real Estate, and Commercial Spaces in the heart of Adama.";
        $btn_text   = "Search Properties";
    ?>

    <header class="hero">
        <img src="adama1.webp" alt="Adama City" class="bg-image">

        <nav class="top-nav">
          <div class="dropdown">
    <a href="#" style="font-size: 20px;"><i class="fas fa-user-circle"></i></a>
    <div class="dropdown-content">
        <a href="admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
        <a href="login.php"><i class="fas fa-user"></i> Guest/Landlord</a>
    </div>
</div>
            
        </nav>

        <div class="hero-content">
            <h1><?php echo $hero_title; ?></h1>
            <p><?php echo $hero_desc; ?></p><br>
            <a href="Index.php" class="btn"><?php echo $btn_text; ?></a>
        </div>
    </header>

    <section class="categories">
        <div class="cat-card">
            <i class="fas fa-bed"></i>
            <h3>Simple Homes</h3>
            <p>Rooms & Traditional Houses for Students and Workers.</p>
        </div>
        
        <div class="cat-card">
            <i class="fas fa-building"></i>
            <h3>Real Estate</h3>
            <p>Modern Apartments and Luxury Villas in Adama.</p>
        </div>
        
        <div class="cat-card">
            <i class="fas fa-store"></i>
            <h3>Commercial</h3>
            <p>Offices, Shops, and Warehouses for Businesses.</p>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Adama Property Hub. All rights reserved.</p>
    </footer>

</body>
</html>