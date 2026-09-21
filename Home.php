<?php include('includes/session_config.php'); session_start(); include('includes/db.php'); include('includes/lang.php');
$total_houses  = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM houses"))[0];
$total_landlords = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE is_admin=0"))[0];
$total_kebeles = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(DISTINCT kebele) FROM houses"))[0];
$total_tenants = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM requests WHERE status=0"))[0];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AdamaRent - Property Rental Marketplace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Inter',system-ui,-apple-system,sans-serif;color:#1e293b;overflow-x:hidden}

/* NAVBAR */
.navbar{position:fixed;top:0;left:0;right:0;z-index:1000;padding:16px 40px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;background:#0f172a;box-shadow:0 4px 30px rgba(0,0,0,.15);transition:all .3s ease}
.navbar.scrolled{padding:12px 40px}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;justify-self:start}
.nav-brand-icon{width:40px;height:40px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:18px}
.nav-brand-text{color:#fff;font-size:20px;font-weight:800;letter-spacing:-.5px}
.nav-brand-text span{color:#2dd4bf}
.nav-links{display:flex;align-items:center;gap:8px;justify-self:end}
.nav-center{display:flex;align-items:center;gap:4px;justify-self:center;border-radius:12px;padding:4px;backdrop-filter:blur(10px)}
.nav-center a{color:rgba(255,255,255,.75);text-decoration:none;font-size:14px;font-weight:500;padding:8px 18px;border-radius:10px;position:relative;transition:background .25s cubic-bezier(.4,0,.2,1),color .25s}
.nav-center a::after{content:'';position:absolute;left:18px;bottom:6px;width:0;height:2px;border-radius:2px;background:linear-gradient(90deg,#2dd4bf,#14b8a6);transition:width .3s cubic-bezier(.4,0,.2,1)}
.nav-center a:hover{color:#fff;background:rgba(255,255,255,.12)}
.nav-center a:hover::after{width:calc(100% - 36px)}
.nav-links a{color:rgba(255,255,255,.85);text-decoration:none;font-size:14px;font-weight:500;padding:8px 16px;border-radius:8px;position:relative;transition:background .25s cubic-bezier(.4,0,.2,1),color .25s}
.nav-links a::after{content:'';position:absolute;left:16px;bottom:5px;width:0;height:2px;border-radius:2px;background:linear-gradient(90deg,#2dd4bf,#14b8a6);transition:width .3s cubic-bezier(.4,0,.2,1)}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,.1)}
.nav-links a:hover::after{width:calc(100% - 32px)}
.nav-links .btn-nav{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;padding:10px 22px;border-radius:10px;font-weight:600;transition:transform .25s cubic-bezier(.34,1.56,.64,1),box-shadow .25s}
.nav-links .btn-nav:hover{transform:translateY(-1px);box-shadow:0 4px 15px rgba(13,148,136,.4)}
.nav-links .btn-nav i{transition:transform .35s cubic-bezier(.34,1.56,.64,1)}
.nav-links .btn-nav:hover i{transform:rotate(90deg) scale(1.15)}
.user-avatar-wrap{position:relative}
.user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;cursor:pointer;border:2px solid rgba(255,255,255,.2);transition:all .2s}
.user-avatar:hover{border-color:rgba(255,255,255,.5);transform:scale(1.05)}
.user-dropdown{position:absolute;top:calc(100% + 8px);right:0;width:220px;background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:opacity .3s cubic-bezier(.34,1.56,.64,1),transform .3s cubic-bezier(.34,1.56,.64,1),visibility .3s;z-index:1001}
.user-avatar-wrap:hover .user-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
.user-dropdown-header{padding:16px;display:flex;align-items:center;gap:10px}
.user-avatar-sm{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-dropdown-name{color:#f1f5f9;font-size:13px;font-weight:600}
.user-dropdown-role{color:#94a3b8;font-size:11px}
.user-dropdown-divider{height:1px;background:rgba(255,255,255,.08)}
.user-dropdown a{display:flex;align-items:center;gap:8px;padding:10px 16px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:all .15s}
.user-dropdown a:hover{background:rgba(255,255,255,.05);color:#fff}
.user-dropdown a.logout{color:#f87171;border-top:1px solid rgba(255,255,255,.08)}
        .user-dropdown a.logout:hover{background:rgba(248,113,113,.1);color:#fca5a5}
        .user-dropdown-lang-title{display:flex;align-items:center;gap:8px;padding:12px 18px 8px;color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px}
        .user-dropdown-lang-title i{color:#2dd4bf;font-size:11px}
        .user-dropdown-lang{padding:2px 8px 12px}
        .user-dropdown-lang a{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:9px;color:rgba(255,255,255,.75);text-decoration:none;font-size:13px;font-weight:600;transition:background .15s,color .15s}
        .user-dropdown-lang a:hover{background:rgba(255,255,255,.07);color:#fff}
        .user-dropdown-lang a .lg-badge{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.85);display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0;letter-spacing:.5px}
        .user-dropdown-lang a .lg-radio{width:16px;height:16px;margin-left:auto;border-radius:50%;border:2px solid rgba(255,255,255,.28);position:relative;flex-shrink:0;transition:border-color .2s}
        .user-dropdown-lang a.active{background:rgba(13,148,136,.22);color:#5eead4}
        .user-dropdown-lang a.active .lg-badge{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;box-shadow:0 4px 10px rgba(13,148,136,.45)}
        .user-dropdown-lang a.active .lg-radio{border-color:#2dd4bf}
        .user-dropdown-lang a.active .lg-radio::after{content:'';position:absolute;inset:3px;border-radius:50%;background:#2dd4bf}
.lang-drop{position:relative;display:inline-flex}
.lang-pill{display:inline-flex;align-items:center;gap:7px;color:#fff;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:50px;padding:9px 16px;font-weight:600;font-size:13px;text-decoration:none;transition:all .2s;cursor:pointer;font-family:'Poppins',sans-serif}
.lang-pill:hover{background:rgba(255,255,255,.16);border-color:rgba(45,212,191,.4)}
.lang-pill .lg-code{color:#2dd4bf}
.lang-pill .chev{margin-left:3px;font-size:10px;color:#94a3b8}
.lang-menu{position:absolute;top:calc(100% + 10px);right:0;min-width:200px;background:#1e293b;border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:6px;box-shadow:0 20px 40px rgba(0,0,0,.35);opacity:0;visibility:hidden;transform:translateY(-6px);transition:all .22s cubic-bezier(.34,1.56,.64,1);z-index:1201}
.lang-drop.open .lang-menu{opacity:1;visibility:visible;transform:translateY(0)}
.lang-menu a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:rgba(255,255,255,.75);text-decoration:none;font-size:13.5px;font-weight:600;transition:background .15s}
.lang-menu a:hover{background:rgba(255,255,255,.08);color:#fff}
.lang-menu a.active{background:rgba(13,148,136,.16);color:#2dd4bf}
.lang-menu a .lg-badge{width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
.lang-menu a.active .lg-badge{background:rgba(13,148,136,.3);color:#5eead4}
.lang-menu a .lg-check{margin-left:auto;color:#2dd4bf;font-size:12px}

/* HERO */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0f172a}
.hero-bg{position:absolute;inset:0}
.hero-bg img{width:100%;height:100%;object-fit:cover;opacity:.35}
.hero-gradient{position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,23,42,.92) 0%,rgba(15,23,42,.7) 50%,rgba(13,148,136,.3) 100%)}
.hero-content{position:relative;z-index:2;text-align:center;max-width:800px;padding:0 24px}
.hero-content h1{color:#fff;font-size:clamp(36px,5.5vw,64px);font-weight:900;line-height:1.1;letter-spacing:-1.5px;margin-bottom:20px}
.hero-content h1 span{background:linear-gradient(135deg,#2dd4bf,#0d9488);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-content p{color:rgba(255,255,255,.7);font-size:clamp(16px,2vw,20px);line-height:1.7;margin-bottom:36px;max-width:600px;margin-left:auto;margin-right:auto}
.hero-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.btn-hero{display:inline-flex;align-items:center;gap:10px;padding:16px 32px;border-radius:14px;font-size:16px;font-weight:700;text-decoration:none;transition:all .3s;border:none;cursor:pointer}
.btn-hero-primary{background:#fff;color:#000;box-shadow:0 4px 20px rgba(0,0,0,.2)}
.btn-hero-primary:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.btn-hero-secondary{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(10px)}
.btn-hero-secondary:hover{background:rgba(255,255,255,.15);transform:translateY(-3px)}

/* STATS BAR */
.stats-bar{background:#fff;padding:40px 24px;box-shadow:0 4px 20px rgba(0,0,0,.05)}
.stats-grid{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center}
.stat-item h3{font-size:clamp(28px,3vw,40px);font-weight:900;color:#0d9488;line-height:1}
.stat-item p{font-size:13px;color:#64748b;margin-top:6px;font-weight:500;text-transform:uppercase;letter-spacing:.5px}

/* CATEGORIES */
.categories{padding:80px 24px;background:#f8fafc}
.section-header{text-align:center;margin-bottom:48px}
.section-header .tag{display:inline-block;background:rgba(13,148,136,.1);color:#0d9488;padding:6px 16px;border-radius:50px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px}
.section-header h2{font-size:clamp(28px,3.5vw,40px);font-weight:800;color:#0f172a;letter-spacing:-.5px}
.section-header p{color:#64748b;font-size:16px;margin-top:8px;max-width:500px;margin-left:auto;margin-right:auto}
.cat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1000px;margin:0 auto}
.cat-card{background:#fff;border-radius:16px;padding:36px 28px;text-align:center;transition:all .3s;border:1px solid #f1f5f9;cursor:pointer;text-decoration:none;color:inherit}
.cat-card:hover{transform:translateY(-8px);box-shadow:0 20px 40px rgba(0,0,0,.08);border-color:#e2e8f0}
.cat-icon{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:28px;transition:all .3s}
.cat-card:nth-child(1) .cat-icon{background:linear-gradient(135deg,#ede9fe,#c4b5fd);color:#7c3aed}
.cat-card:nth-child(2) .cat-icon{background:linear-gradient(135deg,#dbeafe,#93c5fd);color:#2563eb}
.cat-card:nth-child(3) .cat-icon{background:linear-gradient(135deg,#d1fae5,#6ee7b7);color:#059669}
.cat-card:hover .cat-icon{transform:scale(1.1)}
.cat-card h3{font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a}
.cat-card p{font-size:14px;color:#64748b;line-height:1.6}

/* HOW IT WORKS */
.how-it-works{padding:80px 24px;background:#fff}
.steps-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;max-width:1000px;margin:0 auto}
.step{text-align:center;padding:32px 20px;position:relative}
.step-number{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;margin:0 auto 20px}
.step h3{font-size:18px;font-weight:700;margin-bottom:8px;color:#0f172a}
.step p{font-size:14px;color:#64748b;line-height:1.6}

/* FOOTER */
.footer{background:#0f172a;color:rgba(255,255,255,.7);padding:60px 24px 30px}
.footer-grid{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px}
.footer-brand h3{color:#fff;font-size:20px;font-weight:800;margin-bottom:12px}
.footer-brand h3 span{color:#2dd4bf}
.footer-brand p{font-size:14px;line-height:1.8;margin-bottom:20px}
.footer-col h4{color:#fff;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}
.footer-col a{display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0;transition:color .2s}
.footer-col a:hover{color:#2dd4bf}
.footer-social{display:flex;gap:10px}
.footer-social a{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all .3s;font-size:16px}
.footer-social a.tg{background:rgba(0,136,204,.2);color:#0088cc}
.footer-social a.fb{background:rgba(24,119,242,.2);color:#1877f2}
.footer-social a.ig{background:rgba(225,48,108,.2);color:#e1306c}
.footer-social a.em{background:rgba(234,67,53,.2);color:#ea4335}
.footer-social a:hover{transform:translateY(-3px)}
.footer-bottom{max-width:1100px;margin:40px auto 0;padding-top:24px;border-top:1px solid rgba(255,255,255,.08);text-align:center;font-size:13px;color:rgba(255,255,255,.4)}

@media(max-width:768px){
.navbar{padding:12px 20px}
.nav-links a:not(.btn-nav),.nav-links:not(:has(a.btn-nav)) a{display:none}
.lang-drop{display:inline-flex !important;position:relative;z-index:1100}
.stats-grid{grid-template-columns:repeat(2,1fr);gap:16px}
.cat-grid{grid-template-columns:1fr}
.steps-grid{grid-template-columns:1fr}
.footer-grid{grid-template-columns:1fr 1fr}
.hero-content h1{font-size:32px}
}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
    <a href="Home.php" class="nav-brand">
        <div class="nav-brand-icon">AR</div>
        <div class="nav-brand-text">Adama<span>Rent</span></div>
    </a>
    <div class="nav-center">
        <a href="index.php"><?php echo t('nav_home'); ?></a>
        <a href="#property-types"><?php echo t('nav_property_types'); ?></a>
        <a href="#how-it-works"><?php echo t('nav_how_it_works'); ?></a>
        <a href="#contact"><?php echo t('nav_contact'); ?></a>
    </div>
    <div class="nav-links">
        <?php if(!isset($_SESSION['user_id'])): ?>
        <div class="lang-drop">
            <button type="button" class="lang-pill" onclick="toggleLangMenu(this)" aria-label="Choose language"><i class="fas fa-globe"></i> <span class="lg-code"><?php echo htmlspecialchars(strtoupper($lang)); ?></span> <i class="fas fa-chevron-down chev"></i></button>
            <div class="lang-menu">
                <?php $languages = ['en' => 'English', 'am' => 'አማርኛ', 'om' => 'Afaan Oromoo']; $codes = ['en' => 'EN', 'am' => 'አማ', 'om' => 'OM']; foreach($languages as $lcode => $lname) { ?>
                <a href="<?php echo lang_switch_url($lcode); ?>" class="<?php echo $lang === $lcode ? 'active' : ''; ?>"><span class="lg-badge"><?php echo $codes[$lcode]; ?></span><?php echo $lname; ?><?php if($lang === $lcode) { ?><i class="fas fa-check lg-check"></i><?php } ?></a>
                <?php } ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-avatar-wrap">
                <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))); ?></div>
                <div class="user-dropdown">
                    <div class="user-dropdown-header">
                        <div class="user-avatar-sm"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))); ?></div>
                        <div><div class="user-dropdown-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                        <div class="user-dropdown-role"><?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 1 ? t('role_admin') : t('role_landlord'); ?></div></div>
                    </div>
                    <div class="user-dropdown-divider"></div>
                    <a href="manage_houses.php"><i class="fas fa-th-large"></i> <?php echo t('nav_dashboard'); ?></a>
                    <a href="profile.php"><i class="fas fa-user"></i> <?php echo t('nav_profile'); ?></a>
                    <div class="user-dropdown-divider"></div>
                    <div class="user-dropdown-lang-title"><i class="fas fa-globe"></i> <?php echo t('lang_label'); ?></div>
                    <div class="user-dropdown-lang">
                        <?php $languages = ['en' => 'English', 'am' => 'አማርኛ', 'om' => 'Afaan Oromoo']; $codes = ['en' => 'EN', 'am' => 'አማ', 'om' => 'OM']; foreach($languages as $lcode => $lname) { ?>
<a href="<?php echo lang_switch_url($lcode); ?>" class="<?php echo $lang === $lcode ? 'active' : ''; ?>"><span class="lg-badge"><?php echo $codes[$lcode]; ?></span><span class="lg-name"><?php echo $lname; ?></span><span class="lg-radio"></span></a>
                        <?php } ?>
                    </div>
                    <div class="user-dropdown-divider"></div>
                    <a href="logout.php" class="logout"><i class="fas fa-right-from-bracket"></i> <?php echo t('nav_signout'); ?></a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-nav"><i class="fas fa-right-to-bracket"></i> <?php echo t('nav_login'); ?></a>
        <?php endif; ?>
    </div>
</nav>

<header class="hero">
    <div class="hero-bg">
        <img src="images/IMG_7182.jpeg" alt="">
        <div class="hero-gradient"></div>
    </div>
    <div class="hero-content">
        <h1><?php echo t('hero_title1'); ?><span><?php echo t('hero_title2'); ?></span><?php echo t('hero_title3'); ?></h1>
        <p><?php echo t('hero_desc'); ?></p>
        <div class="hero-actions">
            <a href="index.php" class="btn-hero btn-hero-primary"><i class="fas fa-search"></i> <?php echo t('btn_get_started'); ?></a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'post_house.php' : 'register.php'; ?>" class="btn-hero btn-hero-secondary"><i class="fas fa-plus-circle"></i> <?php echo t('btn_list_property'); ?></a>
        </div>
    </div>
</header>

<section class="stats-bar">
    <div class="stats-grid">
        <div class="stat-item"><h3><?php echo $total_houses ?>+</h3><p><?php echo t('stat_properties'); ?></p></div>
        <div class="stat-item"><h3><?php echo $total_landlords ?>+</h3><p><?php echo t('stat_landlords'); ?></p></div>
        <div class="stat-item"><h3><?php echo $total_kebeles ?>+</h3><p><?php echo t('stat_kebeles'); ?></p></div>
        <div class="stat-item"><h3><?php echo $total_tenants ?>+</h3><p><?php echo t('stat_tenants'); ?></p></div>
    </div>
</section>

<section class="categories" id="property-types">
    <div class="section-header">
        <div class="tag"><?php echo t('cat_tag'); ?></div>
        <h2><?php echo t('cat_heading'); ?></h2>
        <p><?php echo t('cat_sub'); ?></p>
    </div>
    <div class="cat-grid">
        <a href="index.php?cat=Single+Home" class="cat-card">
            <div class="cat-icon"><i class="fas fa-house-chimney"></i></div>
            <h3><?php echo t('cat_single'); ?></h3>
            <p><?php echo t('cat_single_desc'); ?></p>
        </a>
        <a href="index.php?cat=Apartment" class="cat-card">
            <div class="cat-icon"><i class="fas fa-building"></i></div>
            <h3><?php echo t('cat_apt'); ?></h3>
            <p><?php echo t('cat_apt_desc'); ?></p>
        </a>
        <a href="index.php?cat=Shop" class="cat-card">
            <div class="cat-icon"><i class="fas fa-store"></i></div>
            <h3><?php echo t('cat_com'); ?></h3>
            <p><?php echo t('cat_com_desc'); ?></p>
        </a>
    </div>
</section>

<section class="how-it-works" id="how-it-works">
    <div class="section-header">
        <div class="tag"><?php echo t('hiw_tag'); ?></div>
        <h2><?php echo t('hiw_heading'); ?></h2>
    </div>
    <div class="steps-grid">
        <div class="step">
            <div class="step-number">1</div>
            <h3><?php echo t('step1_t'); ?></h3>
            <p><?php echo t('step1_d'); ?></p>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <h3><?php echo t('step2_t'); ?></h3>
            <p><?php echo t('step2_d'); ?></p>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <h3><?php echo t('step3_t'); ?></h3>
            <p><?php echo t('step3_d'); ?></p>
        </div>
    </div>
</section>

<footer class="footer" id="contact">
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>Adama<span>Rent</span></h3>
            <p><?php echo t('footer_tagline'); ?></p>
            <div class="footer-social">
                <a href="https://t.me/Get_dope" target="_blank" class="tg"><i class="fab fa-telegram-plane"></i></a>
                <a href="https://facebook.com/Getdope" target="_blank" class="fb"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com/get_dope_men" target="_blank" class="ig"><i class="fab fa-instagram"></i></a>
                <a href="mailto:getahun930@gmail.com" class="em"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4><?php echo t('footer_quick'); ?></h4>
            <a href="index.php"><?php echo t('footer_browse'); ?></a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'post_house.php' : 'register.php'; ?>"><?php echo t('btn_list_property'); ?></a>
            <a href="login.php"><?php echo t('footer_signin'); ?></a>
        </div>
        <div class="footer-col">
            <h4><?php echo t('footer_categories'); ?></h4>
            <a href="index.php?cat=Single+Home"><?php echo t('cat_single'); ?></a>
            <a href="index.php?cat=Apartment"><?php echo t('footer_apartments'); ?></a>
            <a href="index.php?cat=Villa"><?php echo t('footer_villas'); ?></a>
            <a href="index.php?cat=Shop"><?php echo t('footer_shops'); ?></a>
        </div>
        <div class="footer-col">
            <h4><?php echo t('footer_contact'); ?></h4>
            <a href="#"><i class="fas fa-location-dot"></i> Adama, Oromia, Ethiopia</a>
            <a href="tel:+251910407721"><i class="fas fa-phone"></i> +251 910 407 721</a>
            <a href="tel:+251938272728"><i class="fas fa-phone"></i> +251 938 272 728</a>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> AdamaRent. <?php echo t('footer_rights'); ?>
    </div>
</footer>

<script>
window.addEventListener('scroll',()=>{
    document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50);
});
function toggleLangMenu(btn){
    var drop = btn.closest('.lang-drop');
    var isOpen = drop.classList.contains('open');
    document.querySelectorAll('.lang-drop.open').forEach(function(d){ d.classList.remove('open'); });
    if(!isOpen) drop.classList.add('open');
}
document.addEventListener('click', function(e){
    if(e.target.closest('.lang-drop')) return;
    document.querySelectorAll('.lang-drop.open').forEach(function(d){ d.classList.remove('open'); });
});
</script>
</body>
</html>
