<?php
session_start();
include('db.php');

// 1. Security Guard: Check Rank (Allows Rank 1 and Rank 2)
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) {
    header("Location: login.php"); 
    exit();
}

// 2. Corrected Data Fetching Logic
// Count Landlords (Rank 0)
$landlords = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE is_admin = 0"));

// Count Admins (Rank 1 and Rank 2 combined)
$admins    = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE is_admin >= 1"));

// Total Houses in the system
$total_h   = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM houses"));

// Count Available (Active)
$res_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM houses WHERE status = '0' OR status = 'Available'");
$active_listings = mysqli_fetch_assoc($res_active)['total'];

// Count Rented (Occupied)
$res_rented = mysqli_query($conn, "SELECT COUNT(*) as total FROM houses WHERE status = '1' OR status = 'Rented'");
$occupied_units = mysqli_fetch_assoc($res_rented)['total'];



// Pending Requests (for Rank 1 to see work to do)
$pending_req = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM requests WHERE status = 0"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Admin Console | Adama Rent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-bg: #f8fafc;
            --sidebar-color: #0f172a;
            --card-white: #ffffff;
            --text-main: #1e293b;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --accent-purple: #8b5cf6;
            --accent-red: #ef4444;
        }

        body { font-family: 'Inter', sans-serif; background: var(--primary-bg); color: var(--text-main); margin: 0; display: flex; }

        /* Sidebar Styling */
        .sidebar { width: 280px; background: var(--sidebar-color); color: #fff; height: 100vh; position: fixed; padding: 30px 20px; box-sizing: border-box; }
        .sidebar h2 { font-size: 22px; font-weight: 700; color: var(--accent-blue); margin-bottom: 40px; text-transform: uppercase; }
        .nav-link { display: flex; align-items: center; padding: 14px 18px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.3s; }
        .nav-link i { margin-right: 15px; width: 20px; text-align: center; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: #fff; }
        .nav-link.active { background: var(--accent-blue); }

        /* Main Content */
        .content { margin-left: 280px; padding: 50px; width: 100%; }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 700; margin: 0; }
        .header p { color: #64748b; margin-top: 5px; }

        /* Grid & Clickable Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; }
        .stat-link { text-decoration: none; color: inherit; display: block; }
        .stat-box { background: var(--card-white); padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: 0.3s; position: relative; overflow: hidden; border-top: 5px solid transparent; }
        .stat-box:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .stat-box h3 { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
        .stat-box .number { font-size: 36px; font-weight: 700; margin: 15px 0 0; }
        .stat-box i { position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.05; }
        
        /* Box Accents */
        .border-blue { border-color: var(--accent-blue); }
        .border-purple { border-color: var(--accent-purple); }
        .border-dark { border-color: var(--sidebar-color); }
        .border-green { border-color: var(--accent-green); }
        .border-orange { border-color: var(--accent-orange); }
        .border-red { border-color: var(--accent-red); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>ADAMA RENT</h2>
        <nav>
            <a href="admin_panel.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="admin_manage_users.php" class="nav-link"><i class="fas fa-user-shield"></i> Manage Users</a>
            <a href="admin_manage_houses.php" class="nav-link"><i class="fas fa-building"></i> Manage Houses</a>
            <a href="admin_manage_requests.php" class="nav-link"><i class="fas fa-envelope-open-text"></i> Property Requests</a>
            
            <div style="margin-top: 50px;">
                <a href="logout.php" class="nav-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </nav>
    </aside>

    <main class="content">
        <header class="header">
            <h1>Executive Summary</h1>
            <p>Welcome back:<strong><?php echo $_SESSION['full_name']; ?></strong> <?php echo ($_SESSION['is_admin'] == 2) ? 'Super Admin' : 'Staff'; ?></p>
        </header>

        <section class="stats-grid">
            <!-- Landlords -->
          <!-- Landlords -->
<a href="admin_manage_users.php?role=landlords" class="stat-link">
    <div class="stat-box border-blue">
        <h3>Total Landlords</h3>
        <div class="number"><?php echo $landlords; ?></div>
        <i class="fas fa-users"></i>
    </div>
</a>
            
            <!-- Admins -->
<a href="admin_manage_users.php?role=staff" class="stat-link">
    <div class="stat-box border-purple">
        <h3>Internal Staff</h3>
        <div class="number"><?php echo $admins; ?></div>
        <i class="fas fa-user-lock"></i>
    </div>
</a>

            <!-- Total Inventory -->
            <a href="admin_manage_houses.php" class="stat-link">
                <div class="stat-box border-dark">
                    <h3>Global Inventory</h3>
                    <div class="number"><?php echo $total_h; ?></div>
                    <i class="fas fa-city"></i>
                </div>
            </a>

            <!-- Corrected: Active Listings (Available) -->
             <a href="admin_manage_houses.php?status=Available" class="stat-link">
             
                <div class="stat-box border-green">
                    <h3>Active Listings</h3>
                    <div class="number"><?php echo $active_listings; ?></div>
                    <i class="fas fa-check-double"></i>
                </div>
            </a>

            <!-- Corrected: Occupied Units (Rented) -->
             <a href="admin_manage_houses.php?status=Rented" class="stat-link">
            
                <div class="stat-box border-orange">
                    <h3>Occupied Units</h3>
                    <div class="number"><?php echo $occupied_units; ?></div>
                    <i class="fas fa-key"></i>
                </div>
            </a>

            <!-- Pending Approvals -->
            <a href="admin_manage_requests.php" class="stat-link">
                <div class="stat-box border-red">
                    <h3>Pending Approvals</h3>
                    <div class="number"><?php echo $pending_req; ?></div>
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </a>
        </section>

        <div style="margin-top: 50px; background: #fff; border: 1px solid #e2e8f0; padding: 25px; border-radius: 20px; font-size: 14px; color: #475569; display: flex; align-items: center; gap: 20px;">
            <div style="background: var(--accent-blue); color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-info"></i>
            </div>
            <div>
                <strong>Operational Note:</strong> "Active Listings" reflects all properties currently marked as **Available**. Once a landlord or admin updates a property to **Rented**, it automatically moves to "Occupied Units."
            </div>
        </div>
    </main>

</body>
</html>