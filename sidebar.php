<?php
// Sidebar partial — expects $current_page to be set by the including file
?>
<style>
/* Sidebar - local, high-specificity to avoid page overrides */
.sidebar, .sidebar * { box-sizing: border-box; }
.sidebar { width: 280px !important; background: linear-gradient(180deg,#0f172a 0%, #14233b 100%) !important; color: #ffffff !important; height: 100vh !important; position: fixed !important; padding: 28px 18px !important; border-right: 1px solid rgba(255,255,255,0.03) !important; font-family: 'Inter', system-ui, Arial !important; }
.sidebar .brand { display: flex; align-items: center; gap:10px; font-weight:800; color: #60a5fa !important; font-size:18px !important; text-decoration:none !important; margin-bottom:18px !important; }
.sidebar .brand img{ width:34px; height:34px; border-radius:8px; object-fit:cover }
.nav-link { display:flex !important; align-items:center !important; gap:12px !important; padding:12px 14px !important; color: rgba(255,255,255,0.85) !important; text-decoration:none !important; border-radius:10px !important; margin-bottom:8px !important; transition: all .18s ease !important; }
.nav-link i{ width:20px; text-align:center; font-size:16px }
.nav-link:hover{ transform: translateX(6px); background: rgba(255,255,255,0.03) !important; color: #fff !important }
.nav-link.active{ background: rgba(255,255,255,0.08) !important; color: #fff !important; box-shadow: inset 0 0 0 2px rgba(99,102,241,0.06) !important; }
.sidebar .meta { font-size:12px; color: rgba(255,255,255,0.6); margin-bottom:12px; }
.sidebar-footer { position: absolute !important; bottom: 20px !important; left: 18px !important; right: 18px !important; }
.logout-btn { display:block !important; text-align:center !important; background:#fff !important; color:#ef4444 !important; padding:10px 12px !important; border-radius:8px !important; font-weight:700 !important; text-decoration:none !important; }
</style>

<div class="sidebar">
    <a href="admin_panel.php" class="brand"><img src="adama1.webp" alt="logo"> <span>ADAMA RENT</span></a>
    <div class="meta">Admin Console</div>
    <a href="admin_panel.php" class="nav-link <?= ($current_page == 'admin_panel.php') ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <?php if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); } ?>
    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 1): ?>
    <a href="admin_manage_users.php" class="nav-link <?= ($current_page == 'admin_manage_users.php') ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Manage Users</a>
    <?php endif; ?>
    <a href="admin_manage_houses.php" class="nav-link <?= ($current_page == 'admin_manage_houses.php') ? 'active' : '' ?>"><i class="fas fa-home"></i> Manage Houses</a>
    <a href="admin_manage_requests.php" class="nav-link <?= ($current_page == 'admin_manage_requests.php') ? 'active' : '' ?>"><i class="fas fa-envelope-open-text"></i> Requests</a>
    
    <div class="sidebar-footer">
        <a href="home.php" class="logout-btn" data-no-ajax="1"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </div>
</div>