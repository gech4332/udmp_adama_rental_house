<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) {
    header("Location: admin_login.php"); 
    exit();
}

$landlords = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE is_admin = 0"));
$admins    = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE is_admin >= 1"));
$total_h   = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM houses WHERE NOT (status = 'Pending' OR is_approved = 0 OR is_approved IS NULL)"));

$res_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM houses WHERE (status = '0' OR status = 'Available') AND NOT (status = 'Pending' OR is_approved = 0 OR is_approved IS NULL)");
$active_listings = $res_active ? mysqli_fetch_assoc($res_active)['total'] : 0;

$res_rented = mysqli_query($conn, "SELECT COUNT(*) as total FROM houses WHERE (status = '1' OR status = 'Rented') AND NOT (status = 'Pending' OR is_approved = 0 OR is_approved IS NULL)");
$occupied_units = $res_rented ? mysqli_fetch_assoc($res_rented)['total'] : 0;

$pending_req = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM requests WHERE status = 0"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AdamaRent Admin</title>
    <?php include(__DIR__ . '/header.php'); ?>
    <style>
        .content{margin-left:260px;padding:40px;width:calc(100% - 260px)}
        .header{margin-bottom:32px}
        .header h1{font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
        .header p{color:#64748b;font-size:14px;margin-top:4px}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
        .stat-link{text-decoration:none;color:inherit;display:block}
        .stat-box{background:#fff;padding:24px;border-radius:14px;border:1px solid #f1f5f9;transition:all .3s;position:relative;overflow:hidden}
        .stat-box:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.06);border-color:#e2e8f0}
        .stat-box h3{font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:0;font-weight:600}
        .stat-box .number{font-size:32px;font-weight:800;margin:12px 0 0}
        .stat-box i{position:absolute;right:-8px;bottom:-8px;font-size:64px;opacity:.04}
        .accent-blue{border-top:3px solid #0d9488}
        .accent-purple{border-top:3px solid #8b5cf6}
        .accent-dark{border-top:3px solid #0f172a}
        .accent-green{border-top:3px solid #10b981}
        .accent-orange{border-top:3px solid #f59e0b}
        .accent-red{border-top:3px solid #ef4444}
        .accent-blue .number{color:#0d9488}
        .accent-purple .number{color:#8b5cf6}
        .accent-dark .number{color:#0f172a}
        .accent-green .number{color:#10b981}
        .accent-orange .number{color:#f59e0b}
        .accent-red .number{color:#ef4444}
        .info-bar{margin-top:32px;background:#fff;border:1px solid #f1f5f9;padding:20px;border-radius:14px;font-size:14px;color:#475569;display:flex;align-items:center;gap:16px}
        .info-bar .info-icon{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    </style>
</head>
<body>
    <main class="content">
        <header class="header">
            <h1>Dashboard</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong> &mdash; <?php echo ($_SESSION['is_admin'] == 2) ? 'Super Admin' : 'Staff'; ?></p>
        </header>

        <section class="stats-grid">
            <a href="admin_manage_users.php?role=landlords" class="stat-link">
                <div class="stat-box accent-blue">
                    <h3>Total Landlords</h3>
                    <div class="number"><?php echo $landlords; ?></div>
                    <i class="fas fa-users"></i>
                </div>
            </a>
            <a href="admin_manage_users.php?role=staff" class="stat-link">
                <div class="stat-box accent-purple">
                    <h3>Internal Staff</h3>
                    <div class="number"><?php echo $admins; ?></div>
                    <i class="fas fa-user-shield"></i>
                </div>
            </a>
            <a href="admin_manage_houses.php" class="stat-link">
                <div class="stat-box accent-dark">
                    <h3>Total Inventory</h3>
                    <div class="number"><?php echo $total_h; ?></div>
                    <i class="fas fa-building"></i>
                </div>
            </a>
            <a href="admin_manage_houses.php?status=Available" class="stat-link">
                <div class="stat-box accent-green">
                    <h3>Active Listings</h3>
                    <div class="number"><?php echo $active_listings; ?></div>
                    <i class="fas fa-check-double"></i>
                </div>
            </a>
            <a href="admin_manage_houses.php?status=Rented" class="stat-link">
                <div class="stat-box accent-orange">
                    <h3>Occupied Units</h3>
                    <div class="number"><?php echo $occupied_units; ?></div>
                    <i class="fas fa-key"></i>
                </div>
            </a>
            <a href="admin_manage_requests.php" class="stat-link">
                <div class="stat-box accent-red">
                    <h3>Pending Approvals</h3>
                    <div class="number"><?php echo $pending_req; ?></div>
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </a>
        </section>

        <div class="info-bar">
            <div class="info-icon"><i class="fas fa-info"></i></div>
            <div><strong>Note:</strong> "Active Listings" shows properties marked as Available. Once marked as Rented by a landlord or admin, they move to "Occupied Units."</div>
        </div>
    </main>
</body>
</html>
