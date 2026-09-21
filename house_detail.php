<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/lang.php');
include('includes/security.php');
if(!isset($_SESSION['csrf_token'])) csrf_token();

function dTimeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if($diff < 60) return t('time_just_now');
    if($diff < 3600) return sprintf(t('time_min_ago'), floor($diff/60));
    if($diff < 86400) return sprintf(t('time_hr_ago'), floor($diff/3600));
    if($diff < 604800) return sprintf(t('time_d_ago'), floor($diff/86400));
    return date('M j, Y', strtotime($datetime));
}

$id = (int)($_GET['house'] ?? 0);
$house = null;

if($id > 0){
    $stmt = mysqli_prepare($conn, "SELECT h.*, u.full_name FROM houses h LEFT JOIN users u ON h.user_id = u.id WHERE h.id=? AND h.is_approved=1 AND h.status IN ('Available','Rented')");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);
    $house = $q ? mysqli_fetch_assoc($q) : null;
}

$notFound = !$house;
$photos = [];
if($house){
    if(!empty($house['image'])) $photos[] = $house['image'];
    $gstmt = mysqli_prepare($conn, "SELECT filename FROM house_images WHERE house_id=? ORDER BY sort_order ASC, id ASC");
    mysqli_stmt_bind_param($gstmt, "i", $id);
    mysqli_stmt_execute($gstmt);
    $gq = mysqli_stmt_get_result($gstmt);
    if($gq){
        while($g = mysqli_fetch_assoc($gq)){
            if(!empty($g['filename'])) $photos[] = $g['filename'];
        }
    }
    $photos = array_values(array_unique($photos));
}

$status    = $house['status'] ?? '';
$isAvail   = ($status === 'Available');
$ownerPhone = preg_replace('/[^0-9+]/', '', $house['phone'] ?? '');
$pendingReqId = 0;
$callState = 'ask';
if(isset($_SESSION['user_id']) && $id > 0){
    $me = (int)$_SESSION['user_id'];
    $rc_stmt = mysqli_prepare($conn, "SELECT id FROM rental_requests WHERE user_id=? AND house_id=? AND status='pending' LIMIT 1");
    mysqli_stmt_bind_param($rc_stmt, "ii", $me, $id);
    mysqli_stmt_execute($rc_stmt);
    $rc = mysqli_stmt_get_result($rc_stmt);
    if($rc && ($rrow = mysqli_fetch_assoc($rc))) $pendingReqId = (int)$rrow['id'];
    
    $cr_stmt = mysqli_prepare($conn, "SELECT status FROM rental_requests WHERE user_id=? AND house_id=? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($cr_stmt, "ii", $me, $id);
    mysqli_stmt_execute($cr_stmt);
    $cr = mysqli_stmt_get_result($cr_stmt);
    if($cr && ($crow = mysqli_fetch_assoc($cr))){
        if($crow['status'] === 'accepted') $callState = 'call';
        elseif($crow['status'] === 'pending') $callState = 'wait';
    }
}
$amenities = [];
if($house){
    $astmt = mysqli_prepare($conn, "SELECT a.id, a.name, a.icon FROM house_amenities ha JOIN amenities a ON ha.amenity_id = a.id WHERE ha.house_id = ? ORDER BY a.sort_order ASC");
    mysqli_stmt_bind_param($astmt, "i", $id);
    mysqli_stmt_execute($astmt);
    $aq = mysqli_stmt_get_result($astmt);
    if($aq){
        while($a = mysqli_fetch_assoc($aq)){
            $amenities[] = $a;
        }
    }
}
$rentHref  = isset($_SESSION['user_id'])
    ? '#rent'
    : 'login.php?redirect=' . urlencode('rent_request.php?house=' . $id);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $notFound ? htmlspecialchars(t('hd_notfound_title')) : htmlspecialchars(t('hd_title_kebele')) . htmlspecialchars($house['kebele']) . ' - AdamaRent'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;display:flex;flex-direction:column}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,.15)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{position:relative;color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:background .25s cubic-bezier(.4,0,.2,1),color .25s}
        .nav-right a::after{content:'';position:absolute;left:14px;bottom:5px;width:0;height:2px;border-radius:2px;background:linear-gradient(90deg,#2dd4bf,#14b8a6);transition:width .3s cubic-bezier(.4,0,.2,1)}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
        .nav-right a:hover::after{width:calc(100% - 28px)}
        .nav-right a i{transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
        .nav-right a:hover i{transform:translateX(3px)}
        .nav-right .btn-accent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
        .nav-right .btn-accent:hover{box-shadow:0 4px 15px rgba(13,148,136,.4);transform:translateY(-1px)}
        .nav-right .btn-accent:hover i{transform:rotate(90deg) scale(1.15)}
        .lang-drop{position:relative;display:inline-flex}
        .lang-pill{display:inline-flex;align-items:center;gap:7px;color:#fff;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:50px;padding:8px 14px;font-weight:600;font-size:13px;text-decoration:none;transition:all .2s;cursor:pointer;font-family:'Inter',sans-serif}
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
        .user-avatar-wrap{position:relative}
        .user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;cursor:pointer;border:2px solid rgba(255,255,255,.2);transition:all .2s}
        .user-avatar:hover{border-color:rgba(255,255,255,.5);transform:scale(1.05)}
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;width:230px;background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s;z-index:1001}
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
        .user-dropdown-lang-title{display:flex;align-items:center;gap:8px;padding:10px 16px 4px;color:#64748b;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
        .user-dropdown-lang a .lg-badge{width:26px;height:26px;border-radius:7px;background:rgba(255,255,255,.08);display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0}
        .user-dropdown-lang a.active{color:#2dd4bf}
        .user-dropdown-lang a.active .lg-badge{background:rgba(13,148,136,.3);color:#5eead4}
        .user-dropdown-lang .lg-check{margin-left:auto;color:#2dd4bf;font-size:12px}

        /* LAYOUT */
        .page{max-width:1150px;margin:0 auto;width:100%;padding:20px 24px 60px;flex:1}

        .notfound{max-width:560px;margin:80px auto;text-align:center;background:#fff;border-radius:20px;padding:56px 40px;border:1px solid #f1f5f9}
        .notfound i{font-size:54px;color:#94a3b8;margin-bottom:18px;display:block}
        .notfound h1{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:10px}
        .notfound p{color:#64748b;font-size:14px;line-height:1.7;margin-bottom:24px}
        .notfound a{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:11px;text-decoration:none;font-weight:700;font-size:14px;transition:all .3s}
        .notfound a:hover{box-shadow:0 6px 20px rgba(13,148,136,.4);transform:translateY(-1px)}

        .layout{display:grid;grid-template-columns:620px 1fr;gap:24px;align-items:start}

        /* GALLERY */
        .gallery{background:#fff;border-radius:18px;overflow:hidden;border:1px solid #f1f5f9;box-shadow:0 2px 14px rgba(0,0,0,.04)}
        .g-main{position:relative;height:440px;background:#0f172a}
        .g-main img{width:100%;height:100%;object-fit:cover}
        .g-nav{position:absolute;top:50%;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.92);border:none;color:#0f172a;font-size:14px;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25);transition:all .2s;display:flex;align-items:center;justify-content:center}
        .g-nav:hover{background:#fff;transform:translateY(-50%) scale(1.08)}
        .g-nav.prev{left:14px}
        .g-nav.next{right:14px}
        .g-count{position:absolute;bottom:14px;right:14px;background:rgba(15,23,42,.75);color:#fff;font-size:12px;font-weight:600;padding:6px 12px;border-radius:50px;backdrop-filter:blur(6px)}
        .g-thumbs{display:flex;gap:10px;padding:12px;overflow-x:auto}
        .g-thumbs img{width:86px;height:64px;object-fit:cover;border-radius:9px;border:2px solid transparent;cursor:pointer;transition:all .2s;flex-shrink:0}
        .g-thumbs img:hover{transform:translateY(-2px)}
        .g-thumbs img.on{border-color:#0d9488;box-shadow:0 3px 10px rgba(13,148,136,.3)}

        /* INFO PANEL */
        .info{background:#fff;border-radius:18px;border:1px solid #f1f5f9;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,.04)}
        .info-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px}
        .chip{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
        .chip-avail{background:rgba(16,185,129,.12);color:#059669}
        .chip-rented{background:rgba(239,68,68,.12);color:#dc2626}
        .chip-cat{background:#f1f5f9;color:#475569}
        .info-price{font-size:34px;font-weight:800;color:#0d9488;letter-spacing:-1px;margin-bottom:2px}
        .info-price span{font-size:15px;font-weight:500;color:#94a3b8;letter-spacing:0}
        .info-loc{display:flex;align-items:center;gap:8px;color:#64748b;font-size:14px;margin-bottom:22px}
        .info-loc i{color:#0d9488}
        .info-title{font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:0 0 10px;padding-bottom:10px;border-bottom:1px solid #f1f5f9}
        .info-desc{font-size:14px;color:#475569;line-height:1.75;margin-bottom:22px;white-space:pre-line}

        .facts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:22px}
        .fact{background:#f8fafc;border:1px solid #f1f5f9;border-radius:11px;padding:12px 14px}
        .fact .k{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
        .fact .v{font-size:14px;color:#0f172a;font-weight:700;margin-top:3px;word-break:break-word}

        .detail-amenities{display:flex;flex-wrap:wrap;gap:8px}
        .detail-amenity{display:inline-flex;align-items:center;gap:7px;background:#f0fdfa;border:1px solid #ccfbf1;color:#0f766e;padding:7px 13px;border-radius:9px;font-size:12px;font-weight:600}
        .detail-amenity i{color:#0d9488;font-size:12px}
        .amenity-card{background:#fff;border-radius:18px;border:1px solid #f1f5f9;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,.04)}

        .actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
        .btn-action{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px;border-radius:11px;font-size:14px;font-weight:700;text-decoration:none;transition:all .25s;border:none;cursor:pointer;font-family:inherit}
        .btn-rent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;grid-column:1/-1}
        .btn-rent:hover{box-shadow:0 6px 20px rgba(13,148,136,.45);transform:translateY(-1px)}
        .btn-call{background:rgba(13,148,136,.1);color:#0d9488}
        .btn-call:hover{background:#0d9488;color:#fff}
        .btn-map{background:rgba(59,130,246,.1);color:#3b82f6}
        .btn-map:hover{background:#3b82f6;color:#fff}
        .rented-note{grid-column:1/-1;background:#f8faf9;border:1px dashed #e2e8f0;color:#94a3b8;border-radius:11px;padding:14px;text-align:center;font-size:13px;font-weight:600}
        .rented-note.rented-ok{background:#f0fdfa;border:1px solid #ccfbf1;color:#0f766e}
        .btn-rent:disabled{opacity:.65;cursor:default;transform:none;box-shadow:none}

        /* OWNER CARD */
        .owner{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:14px}
        .owner-av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;flex-shrink:0}
        .owner-name{font-size:14px;font-weight:700;color:#0f172a}
        .owner-sub{font-size:12px;color:#94a3b8;margin-top:2px}

        /* SUMMARY CARD */
        .summary{background:#fff;border-radius:18px;border:1px solid #f1f5f9;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,.04)}
        .summary h3{font-size:15px;font-weight:800;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:8px}
        .summary h3 i{color:#0d9488}
        .summary ul{list-style:none}
        .summary li{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #f8fafc;font-size:13px}
        .summary li:last-child{border-bottom:none}
        .summary li span{color:#94a3b8}
        .summary li strong{color:#334155;font-weight:600;text-align:right}

        @media(max-width:1080px){
            .layout{grid-template-columns:1fr}
            .g-main{height:300px}
        }
        @media(max-width:560px){
            .navbar{padding:12px 16px}
            .page{padding:16px;padding-bottom:48px}
            .info{padding:20px}
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/pending_invite_notice.php'); ?>
    <nav class="navbar">
        <a href="Home.php" class="nav-brand">
            <div class="nav-brand-icon">AR</div>
            <div class="nav-brand-text">Adama<span>Rent</span></div>
        </a>
        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="post_house.php" class="btn-accent"><i class="fas fa-plus"></i> <?php echo t('new_posts'); ?></a>
                <a href="profile.php"><i class="fas fa-user-circle"></i> <?php echo t('nav_profile'); ?></a>
                <a href="manage_houses.php"><i class="fas fa-th-large"></i> <?php echo t('nav_dashboard'); ?></a>
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
                            <a href="<?php echo lang_switch_url($lcode); ?>" class="<?php echo $lang === $lcode ? 'active' : ''; ?>"><span class="lg-badge"><?php echo $codes[$lcode]; ?></span><?php echo $lname; ?><?php if($lang === $lcode) { ?><i class="fas fa-check lg-check"></i><?php } ?></a>
                            <?php } ?>
                        </div>
                        <div class="user-dropdown-divider"></div>
                        <a href="logout.php" class="logout"><i class="fas fa-right-from-bracket"></i> <?php echo t('nav_signout'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-right-to-bracket"></i> <?php echo t('nav_login'); ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if($notFound): ?>
        <div class="notfound">
            <i class="fas fa-house-circle-xmark"></i>
            <h1><?php echo t('hd_notfound_h'); ?></h1>
            <p><?php echo t('hd_notfound_p'); ?></p>
            <a href="index.php"><i class="fas fa-arrow-left"></i> <?php echo t('hd_back_listings'); ?></a>
        </div>
    <?php else: ?>
    <div class="page">
        <div class="layout">
            <!-- GALLERY -->
            <div class="gallery">
                <div class="g-main">
                    <img id="mainImg" src="uploads/<?php echo htmlspecialchars($photos[0]); ?>" alt="<?php echo htmlspecialchars(t('hd_photo_alt')); ?>">
                    <?php if(count($photos) > 1): ?>
                        <button class="g-nav prev" onclick="stepPhoto(-1)" aria-label="<?php echo htmlspecialchars(t('hd_prev_photo')); ?>"><i class="fas fa-chevron-left"></i></button>
                        <button class="g-nav next" onclick="stepPhoto(1)" aria-label="<?php echo htmlspecialchars(t('hd_next_photo')); ?>"><i class="fas fa-chevron-right"></i></button>
                        <span class="g-count" id="photoCount">1 / <?php echo count($photos); ?></span>
                    <?php endif; ?>
                </div>
                <?php if(count($photos) > 1): ?>
                <div class="g-thumbs">
                    <?php foreach($photos as $i => $p): ?>
                        <img src="uploads/<?php echo htmlspecialchars($p); ?>" class="<?php echo $i === 0 ? 'on' : ''; ?>" data-idx="<?php echo $i; ?>" onclick="gotoPhoto(<?php echo $i; ?>)" alt="<?php echo htmlspecialchars(t('hd_photo_alt')); ?>">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- INFO -->
            <div class="info">
                <div class="info-head">
                    <span class="chip <?php echo $isAvail ? 'chip-avail' : 'chip-rented'; ?>"><i class="fas fa-circle" style="font-size:6px"></i> <?php echo htmlspecialchars(t_status($status)); ?></span>
                    <span class="chip chip-cat"><?php echo htmlspecialchars($house['category']); ?></span>
                </div>
                <div class="info-price"><?php echo number_format((int)$house['amount']); ?> <span><?php echo t('etb_month'); ?></span></div>
                <div class="info-loc"><i class="fas fa-location-dot"></i> <?php echo t('ph_kebele'); ?> <?php echo htmlspecialchars($house['kebele']); ?><?php echo !empty($house['street']) ? ', ' . htmlspecialchars($house['street']) : ''; ?></div>

                <?php if(!empty($house['description'])): ?>
                    <div class="info-title"><?php echo t('hd_about'); ?></div>
                    <p class="info-desc"><?php echo nl2br(htmlspecialchars($house['description'])); ?></p>
                <?php endif; ?>

                <div class="info-title"><?php echo t('hd_quick_facts'); ?></div>
                <div class="facts">
                    <div class="fact"><div class="k"><?php echo t('ph_kebele'); ?></div><div class="v"><?php echo htmlspecialchars($house['kebele']); ?></div></div>
                    <div class="fact"><div class="k"><?php echo t('hd_street'); ?></div><div class="v"><?php echo htmlspecialchars($house['street']); ?></div></div>
                    <?php if(!empty($house['house_number'])): ?>
                        <div class="fact"><div class="k"><?php echo t('hd_house_no'); ?></div><div class="v"><?php echo htmlspecialchars($house['house_number']); ?></div></div>
                    <?php endif; ?>
                    <div class="fact"><div class="k"><?php echo t('ph_category'); ?></div><div class="v"><?php echo htmlspecialchars($house['category']); ?></div></div>
                </div>

                <div class="actions">
                    <?php if($isAvail): ?>
                        <a href="<?php echo htmlspecialchars($rentHref); ?>" id="rentBtn" class="btn-action btn-rent"><i class="fas <?php echo $pendingReqId ? 'fa-xmark' : 'fa-hand-holding-heart'; ?>"></i> <?php echo $pendingReqId ? t('hd_cancel_request') : t('hd_request_rent'); ?></a>
                        <button type="button" id="callOwnerBtn" class="btn-action btn-call" data-phone="<?php echo htmlspecialchars($house['phone'] ?? ''); ?>"><i class="fas fa-phone"></i> <?php echo t('hd_call_owner'); ?></button>
                    <?php else: ?>
                        <div class="rented-note"><i class="fas fa-lock"></i> <?php echo t('hd_rented_note'); ?></div>
                    <?php endif; ?>
                    <?php if(!empty($house['map_link'])): ?>
                        <a href="<?php echo htmlspecialchars($house['map_link']); ?>" target="_blank" rel="noopener" class="btn-action btn-map"><i class="fas fa-map-location-dot"></i> <?php echo t('hd_view_map'); ?></a>
                    <?php endif; ?>
                </div>

                <div class="owner">
                    <div class="owner-av"><?php echo htmlspecialchars(strtoupper(substr($house['full_name'] ?? 'O', 0, 1))); ?></div>
                    <div>
                        <div class="owner-name"><?php echo htmlspecialchars($house['full_name'] ?? t('hd_property_owner')); ?></div>
                        <div class="owner-sub"><i class="fas fa-clock" style="margin-right:4px"></i><?php echo t('hd_listed'); ?><?php echo dTimeAgo($house['created_at']); ?></div>
                    </div>
                </div>
            </div>

            <?php if(!empty($amenities)): ?>
            <div class="gallery">
                <div class="amenity-card">
                    <div class="info-title"><?php echo t('ph_amenities'); ?></div>
                    <div class="detail-amenities">
                        <?php foreach($amenities as $a): ?>
                            <span class="detail-amenity"><i class="<?php echo htmlspecialchars($a['icon']); ?>"></i> <?php echo htmlspecialchars($a['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php endif; ?>

    <script>
        var hdSigninTitle = <?php echo json_encode(t('hd_signin_required')); ?>;
        var hdSigninMsg = <?php echo json_encode(t('hd_signin_msg')); ?>;
        var hdGoLogin = <?php echo json_encode(t('hd_go_login')); ?>;
        var hdCancelReqTitle = <?php echo json_encode(t('hd_cancel_req_title')); ?>;
        var hdCancelReqMsg = <?php echo json_encode(t('hd_cancel_req_msg')); ?>;
        var hdCancelRequest = <?php echo json_encode(t('hd_cancel_request')); ?>;
        var hdRequestRent = <?php echo json_encode(t('hd_request_rent')); ?>;
        var hdReqMsg = <?php echo json_encode(t('hd_req_msg')); ?>;
        var hdSendReq = <?php echo json_encode(t('hd_send_req')); ?>;
        var hdSrvError = <?php echo json_encode(t('hd_srv_error')); ?>;
        var hdError = <?php echo json_encode(t('hd_error')); ?>;
        var hdCalling = <?php echo json_encode(t('hd_calling')); ?>;
        var hdCallingOwner = <?php echo json_encode(t('hd_calling_owner')); ?>;
        var hdWaitApproved = <?php echo json_encode(t('hd_wait_approved')); ?>;
        var hdReqPending = <?php echo json_encode(t('hd_req_pending')); ?>;
        var hdAskFirst = <?php echo json_encode(t('hd_ask_first')); ?>;
        var hdReqRequired = <?php echo json_encode(t('hd_req_required')); ?>;
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
        <?php if(!$notFound && count($photos) > 1): ?>
        var gallery = <?php echo json_encode($photos); ?>;
        var gIdx = 0;
        function renderPhoto(){
            document.getElementById('mainImg').src = 'uploads/' + gallery[gIdx];
            document.getElementById('photoCount').textContent = (gIdx + 1) + ' / ' + gallery.length;
            document.querySelectorAll('.g-thumbs img').forEach(function(t){
                t.classList.toggle('on', parseInt(t.getAttribute('data-idx'), 10) === gIdx);
            });
        }
        function stepPhoto(dir){
            gIdx = (gIdx + dir + gallery.length) % gallery.length;
            renderPhoto();
        }
        function gotoPhoto(i){
            gIdx = i;
            renderPhoto();
        }
        <?php endif; ?>

        <?php if(!$notFound && $isAvail): ?>
        var rentBtn = document.getElementById('rentBtn');
        if(rentBtn){
            rentBtn.addEventListener('click', function(e){
                e.preventDefault();
                var link = this;
                <?php if(!isset($_SESSION['user_id'])): ?>
                adamaConfirm({
                    title: hdSigninTitle,
                    message: hdSigninMsg,
                    confirmText: hdGoLogin,
                    onConfirm: function(){ window.location = link.href; }
                });
                <?php else: ?>
                var reqId = <?php echo (int)$pendingReqId; ?>;
                var havingRequest = reqId > 0;
                if(havingRequest){
                    adamaConfirm({
                        title: hdCancelReqTitle,
                        message: hdCancelReqMsg,
                        confirmText: hdCancelRequest,
                        onConfirm: function(){
                            var btn = link;
                            btn.disabled = true;
                            var body = new FormData();
                            body.append('action', 'cancel');
                            body.append('id', reqId);
                            body.append('csrf_token', <?php echo json_encode($_SESSION['csrf_token']); ?>);
                            fetch('rental_request_action.php', {method: 'POST', body: body, headers: {'X-Requested-With': 'XMLHttpRequest'}})
                                .then(function(r){ return r.json(); })
                                .then(function(data){
                                    showToast(data.message, data.type, data.title);
                                    btn.disabled = false;
                                    if(data.type === 'success'){
                                        havingRequest = false;
                                        btn.innerHTML = '<i class="fas fa-hand-holding-heart"></i> ' + hdRequestRent;
                                    }
                                })
                                .catch(function(){
                                    btn.disabled = false;
                                    showToast(hdSrvError, 'error', hdError);
                                });
                        }
                    });
                } else {
                    adamaConfirm({
                        title: hdRequestRent,
                        message: hdReqMsg,
                        confirmText: hdSendReq,
                        onConfirm: function(){
                            var btn = link;
                            btn.disabled = true;
                            var body = new FormData();
                            body.append('house', <?php echo (int)$id; ?>);
                            body.append('csrf_token', <?php echo json_encode($_SESSION['csrf_token']); ?>);
                            fetch('rent_request.php', {method: 'POST', body: body, headers: {'X-Requested-With': 'XMLHttpRequest'}})
                                .then(function(r){ return r.json(); })
                                .then(function(data){
                                    if(data.type === 'login_needed'){ window.location = data.redirect; return; }
                                    showToast(data.message, data.type, data.title);
                                    btn.disabled = false;
                                    if(data.type === 'success' || (data.type === 'info' && data.request_id)){
                                        reqId = data.request_id || reqId;
                                        havingRequest = true;
                                        btn.innerHTML = '<i class="fas fa-xmark"></i> ' + hdCancelRequest;
                                    }
                                })
                                .catch(function(){
                                    btn.disabled = false;
                                    showToast(hdSrvError, 'error', hdError);
                                });
                        }
                    });
                }
                <?php endif; ?>
            });
        }
        <?php endif; ?>

        var callOwnerBtn = document.getElementById('callOwnerBtn');
        if(callOwnerBtn){
            callOwnerBtn.addEventListener('click', function(e){
                e.preventDefault();
                var state = <?php echo json_encode($callState); ?>;
                var phone = this.getAttribute('data-phone') || <?php echo json_encode($house['phone'] ?? ''); ?>;
                if(state === 'call'){
                    showToast(hdCalling, 'success', hdCallingOwner);
                    window.location.href = 'tel:' + phone;
                } else if(state === 'wait'){
                    showToast(hdWaitApproved, 'info', hdReqPending);
                } else {
                    showToast(hdAskFirst, 'info', hdReqRequired);
                }
            });
        }

        </script>
    <?php include(__DIR__ . '/includes/popup.php'); ?>
</body>
</html>