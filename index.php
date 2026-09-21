<?php 
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include('includes/lang.php');
if(!isset($_SESSION['csrf_token'])) csrf_token(); 

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

function timeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if($diff < 60) return 'Just now';
    if($diff < 3600) return floor($diff/60) . ' min ago';
    if($diff < 86400) return floor($diff/3600) . ' hr ago';
    if($diff < 604800) return floor($diff/86400) . ' d ago';
    return date('M j', strtotime($datetime));
}

$notifs = [];
$unread_count = 0;
if(isset($_SESSION['user_id'])){
    $uid = (int)$_SESSION['user_id'];
    $nq_stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 10");
    mysqli_stmt_bind_param($nq_stmt, "i", $uid);
    mysqli_stmt_execute($nq_stmt);
    $nq = mysqli_stmt_get_result($nq_stmt);
    if($nq) $notifs = mysqli_fetch_all($nq, MYSQLI_ASSOC);
    
    $cq_stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
    mysqli_stmt_bind_param($cq_stmt, "i", $uid);
    mysqli_stmt_execute($cq_stmt);
    $cq = mysqli_stmt_get_result($cq_stmt);
    if($cq) $unread_count = (int)mysqli_fetch_assoc($cq)['c'];
}

$LISTING_LIMIT = 8;

function quickFilterUrl($catValue) {
    $p = $_GET;
    $p['cat'] = $catValue;
    if(isset($p['ajax'])) unset($p['ajax']);
    return 'index.php?' . http_build_query($p);
}

function buildListingQuery($conn) {
    $where = "houses.status IN ('Available', 'Rented') AND houses.is_approved = 1";
    $types = '';
    $params = [];
    if(!empty($_GET['cat'])) {
        $where .= " AND houses.category = ?";
        $types .= 's';
        $params[] = $_GET['cat'];
    }
    if(!empty($_GET['kb'])) {
        $where .= " AND houses.kebele LIKE ?";
        $types .= 's';
        $params[] = '%' . $_GET['kb'] . '%';
    }
    if(!empty($_GET['max_pr'])) {
        $where .= " AND houses.amount <= ?";
        $types .= 'i';
        $params[] = (int)$_GET['max_pr'];
    }
    $sort = $_GET['sort'] ?? 'newest';
    $order = ($sort == 'price_low') ? 'amount ASC' : (($sort == 'price_high') ? 'amount DESC' : 'created_at DESC');
    return [
        'where'  => $where,
        'types'  => $types,
        'params' => $params,
        'sql'    => "SELECT houses.*, users.full_name FROM houses LEFT JOIN users ON houses.user_id = users.id WHERE $where ORDER BY $order"
    ];
}

function renderPropertyCard($row, $all_amenities, $house_amenities, $house_images) {
    $status = $row['status'] ?? 'Available';
    $badgeClass = ($status == 'Rented') ? 'badge-rented' : 'badge-available';
    $images = $house_images[$row['id']] ?? [];
?>
                <div class="card" data-href="house_detail.php?house=<?php echo (int)$row['id']; ?>">
                    <div class="card-img">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Property" loading="lazy">
                        <span class="card-badge <?php echo $badgeClass; ?>"><i class="fas <?php echo ($status == 'Rented') ? 'fa-circle-check' : 'fa-bolt'; ?>"></i><?php echo htmlspecialchars($status); ?></span>
                        <span class="card-category"><?php echo htmlspecialchars($row['category']); ?></span>
                        <span class="card-photos-count"><i class="fas fa-camera"></i> <?php echo (1 + count($images)); ?></span>
                    </div>
                    <?php if(!empty($images)): ?>
                    <div class="card-thumbs">
                        <?php foreach($images as $img): ?>
                            <img class="card-thumb" src="uploads/<?php echo htmlspecialchars($img); ?>" alt="Property photo" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="card-price"><?php echo number_format($row['amount']); ?><span class="card-price-cur"> <?php echo t('etb_month'); ?></span></div>
                        <div class="card-location">
                            <i class="fas fa-location-dot"></i>
                            Kebele <?php echo htmlspecialchars($row['kebele']); ?>, <?php echo htmlspecialchars($row['street']); ?>
                        </div>
                        <div class="card-desc"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                        <?php if(!empty($house_amenities[$row['id']])): ?>
                        <div class="card-amenities">
                            <?php foreach($house_amenities[$row['id']] as $aid):
                                if(!isset($all_amenities[$aid])) continue;
                            ?>
                                <span class="card-amenity" title="<?php echo htmlspecialchars($all_amenities[$aid]['name']); ?>"><i class="<?php echo htmlspecialchars($all_amenities[$aid]['icon']); ?>"></i><?php echo htmlspecialchars($all_amenities[$aid]['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="card-meta">
                            <div class="card-owner"><span class="owner-dot"></span> <?php echo htmlspecialchars($row['full_name'] ?? 'Private'); ?></div>
                            <span class="card-view"><i class="fas fa-circle-arrow-right"></i></span>
                        </div>
                    </div>
                </div>
<?php
}

function loadAmenities($conn) {
    $all = [];
    $res = mysqli_query($conn, "SELECT id, name, icon FROM amenities");
    if($res) { while($r = mysqli_fetch_assoc($res)) $all[$r['id']] = $r; }
    $map = [];
    $res2 = mysqli_query($conn, "SELECT house_id, amenity_id FROM house_amenities");
    if($res2) { while($r = mysqli_fetch_assoc($res2)) $map[$r['house_id']][] = $r['amenity_id']; }
    return [$all, $map];
}

function loadHouseImages($conn) {
    $map = [];
    $res = mysqli_query($conn, "SELECT house_id, filename FROM house_images ORDER BY sort_order ASC, id ASC");
    if($res) { while($r = mysqli_fetch_assoc($res)) $map[$r['house_id']][] = $r['filename']; }
    return $map;
}

if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: text/html; charset=UTF-8');
    $q = buildListingQuery($conn);
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : $LISTING_LIMIT;
    list($all_amenities, $house_amenities) = loadAmenities($conn);
    $house_images = loadHouseImages($conn);
    
    $stmt = mysqli_prepare($conn, $q['sql'] . " LIMIT ? OFFSET ?");
    $types = $q['types'] . 'ii';
    $params = $q['params'];
    $params[] = $limit;
    $params[] = $offset;
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $count = 0;
    if($res && mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)) {
            renderPropertyCard($row, $all_amenities, $house_amenities, $house_images);
            $count++;
        }
    }
    header('X-Items-Count: ' . $count);
    exit;
}

$q = buildListingQuery($conn);

$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM houses WHERE " . $q['where']);
if($q['types']) {
    mysqli_stmt_bind_param($count_stmt, $q['types'], ...$q['params']);
}
mysqli_stmt_execute($count_stmt);
$total_filtered = (int)mysqli_fetch_row(mysqli_stmt_get_result($count_stmt))[0];

list($all_amenities, $house_amenities) = loadAmenities($conn);
$house_images = loadHouseImages($conn);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Properties - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:#f2f6fa;color:#1e293b;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
        button{font-family:inherit}
        a{font-family:inherit}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 1px 0 rgba(255,255,255,.06),0 8px 24px -16px rgba(0,0,0,.5)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px;box-shadow:0 4px 14px rgba(13,148,136,.35)}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800;letter-spacing:-.3px}
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
        .lang-drop{position:relative;display:inline-flex;margin-right:4px}
        .lang-pill{display:inline-flex;align-items:center;gap:7px;color:#fff;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:50px;padding:8px 15px;font-weight:600;font-size:13px;text-decoration:none;transition:all .2s;cursor:pointer}
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
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;width:220px;background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:opacity .3s cubic-bezier(.34,1.56,.64,1),transform .3s cubic-bezier(.34,1.56,.64,1),visibility .3s;z-index:1001}
        .user-avatar-wrap.open .user-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
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

        /* NOTIFICATION BELL */
        .bell-wrap{position:relative}
        .bell-btn{position:relative;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;transition:background .25s cubic-bezier(.4,0,.2,1),transform .25s}
        .bell-btn:hover{background:rgba(255,255,255,.14);transform:scale(1.08)}
        .bell-btn:hover i{animation:bellShake .5s ease}
        @keyframes bellShake{0%,100%{transform:rotate(0)}20%{transform:rotate(14deg)}40%{transform:rotate(-10deg)}60%{transform:rotate(8deg)}80%{transform:rotate(-4deg)}}
        .bell-btn i{font-size:15px}
        .bell-badge{position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;background:#ef4444;color:#fff;border-radius:50%;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid #0f172a}
        .notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:340px;max-width:calc(100vw - 32px);background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:opacity .3s cubic-bezier(.34,1.56,.64,1),transform .3s cubic-bezier(.34,1.56,.64,1),visibility .3s;z-index:1002}
        .bell-wrap.open .notif-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
        .notif-header{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between}
        .notif-header h4{color:#f1f5f9;font-size:14px;font-weight:700}
        .notif-header .notif-unread-count{color:#94a3b8;font-size:12px}
        .notif-list{max-height:340px;overflow-y:auto}
        .notif-item{display:flex;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.05);text-decoration:none;transition:background .15s}
        .notif-item:hover{background:rgba(255,255,255,.05)}
        .notif-item.unread{background:rgba(13,148,136,.08)}
        .notif-icon{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
        .notif-icon.type-listing{background:linear-gradient(135deg,#3b82f6,#60a5fa)}
        .notif-icon.type-rejection{background:linear-gradient(135deg,#ef4444,#f87171)}
        .notif-text{flex:1;min-width:0}
        .notif-text strong{color:#f1f5f9;font-size:13px;display:block;margin-bottom:2px}
        .notif-text p{color:#94a3b8;font-size:12px;line-height:1.5}
        .notif-text time{color:#64748b;font-size:10px;display:block;margin-top:4px}
        .notif-empty{padding:32px 16px;text-align:center;color:#94a3b8;font-size:13px}
        .notif-empty i{font-size:26px;color:#475569;display:block;margin-bottom:8px}
        .notif-footer{padding:10px 16px;border-top:1px solid rgba(255,255,255,.08)}
        .notif-footer button{width:100%;background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);color:#2dd4bf;padding:9px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s}
        .notif-footer button:hover{background:rgba(13,148,136,.25)}

        /* SEARCH */
        .search-section{position:relative;background:linear-gradient(150deg,#0b2e2b 0%,#115e59 52%,#0d9488 100%);border-bottom:1px solid #082f2c;padding:46px 32px 76px;overflow:hidden}
        .search-section::before{content:'';position:absolute;top:-180px;right:-120px;width:520px;height:520px;background:radial-gradient(circle,rgba(45,212,191,.22),transparent 62%);pointer-events:none}
        .search-section::after{content:'';position:absolute;bottom:-200px;left:-140px;width:560px;height:560px;background:radial-gradient(circle,rgba(45,212,191,.16),transparent 62%);pointer-events:none}
        .search-inner{max-width:1200px;margin:0 auto;position:relative;z-index:1}
        .search-title{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px}
        .search-title-text{min-width:0}
        .search-eyebrow{display:inline-flex;align-items:center;gap:7px;color:#5eead4;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.6px;margin-bottom:10px}
        .search-eyebrow i{font-size:12px}
        .search-title h1{font-size:30px;font-weight:800;color:#ffffff;letter-spacing:-.6px;line-height:1.12}
        .search-title p{color:rgba(255,255,255,.72);font-size:14px;margin-top:8px;line-height:1.65;max-width:560px}
        .search-title .count{flex-shrink:0;display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);color:#fff;padding:9px 18px;border-radius:50px;font-size:12.5px;font-weight:700;border:1px solid rgba(255,255,255,.28);backdrop-filter:blur(8px)}
        .search-title .count i{color:#5eead4;font-size:12px}
        .search-form{display:flex;flex-wrap:wrap;align-items:stretch;background:#fff;border:1px solid #eef2f6;border-radius:20px;padding:10px;box-shadow:0 30px 60px -30px rgba(2,42,38,.55)}
        .search-field{flex:1 1 165px;min-width:165px;display:flex;flex-direction:column;justify-content:center;gap:6px;padding:8px 16px 10px}
        .search-field+.search-field{border-left:1px solid #eef2f6}
        .sf-label{display:flex;align-items:center;gap:6px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#94a3b8}
        .sf-label i{color:#0d9488;font-size:11px}
        .sf-control{display:flex;align-items:center;gap:9px}
        .sf-control>i{color:#94a3b8;font-size:13px;flex-shrink:0;pointer-events:none}
        .sf-control select,.sf-control input{width:100%;border:none;background:transparent;font-family:inherit;font-size:14px;font-weight:600;color:#0f172a;outline:none;appearance:none;padding:0}
        .sf-control select{cursor:pointer}
        .sf-control input{cursor:text}
        .sf-control input::placeholder{color:#9aa7b5;font-weight:500}
        .sf-chev{color:#94a3b8;font-size:10px;flex-shrink:0;pointer-events:none}
        .search-form .btn-search{display:inline-flex;align-items:center;gap:8px;padding:0 26px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:14px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .25s;box-shadow:0 6px 18px rgba(13,148,136,.35)}
        .search-form .btn-search:hover{transform:translateY(-1px);box-shadow:0 12px 28px rgba(13,148,136,.45)}
        .search-form .btn-search:active{transform:translateY(0)}
        .search-form .btn-reset{width:50px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;background:#f8fafc;color:#64748b;border:1.5px solid #e8eef4;border-radius:14px;text-decoration:none;font-size:13px;transition:all .2s}
        .search-form .btn-reset:hover{border-color:#0d9488;color:#0d9488;background:#f0fdfa}
        .search-quick{display:flex;align-items:center;flex-wrap:wrap;gap:9px;margin-top:18px}
        .search-quick .sq-label{display:inline-flex;align-items:center;gap:7px;color:rgba(255,255,255,.8);font-size:13px;font-weight:700;letter-spacing:.3px;margin-right:2px}
        .search-quick .sq-label i{color:#5eead4;font-size:11px}
        .search-quick a{color:rgba(255,255,255,.85);text-decoration:none;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.26);padding:7px 16px;border-radius:50px;font-size:12.5px;font-weight:600;backdrop-filter:blur(6px);transition:all .2s}
        .search-quick a:hover{background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.55);color:#fff}
        .search-quick a.active{background:#fff;color:#0d9488;border-color:#fff;font-weight:700}

        /* GRID */
        .listings{max-width:1200px;margin:0 auto;padding:28px 32px 48px;flex:1;width:100%}
        .card-grid{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:auto;align-items:start;gap:22px}
        @media(max-width:1200px){.card-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.card-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:600px){.card-grid{grid-template-columns:1fr}}
        .card{background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e9eff5;transition:transform .3s cubic-bezier(.2,.7,.3,1),box-shadow .3s,border-color .3s;position:relative;cursor:pointer;display:flex;flex-direction:column}
        .card:hover{transform:translateY(-6px);box-shadow:0 22px 46px -20px rgba(15,23,42,.28);border-color:#dbe5ee}
        .card-img{position:relative;height:215px;overflow:hidden;background:#e9eef4}
        .card-img::after{content:'';position:absolute;inset:auto 0 0 0;height:64px;background:linear-gradient(180deg,transparent,rgba(15,23,42,.35));pointer-events:none}
        .card-img img{width:100%;height:100%;object-fit:cover;transition:transform .55s cubic-bezier(.2,.7,.3,1)}
        .card:hover .card-img img{transform:scale(1.06)}
        .card-badge{position:absolute;top:12px;left:12px;padding:5px 11px;border-radius:50px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;display:inline-flex;align-items:center;gap:5px;backdrop-filter:blur(8px);box-shadow:0 2px 8px rgba(0,0,0,.16)}
        .card-badge i{font-size:9px}
        .badge-available{background:linear-gradient(135deg,#059669,#10b981);color:#fff}
        .badge-rented{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff}
        .card-category{position:absolute;top:12px;right:12px;background:rgba(255,255,255,.94);color:#0f172a;padding:5px 11px;border-radius:50px;font-size:10.5px;font-weight:700;backdrop-filter:blur(8px);box-shadow:0 2px 8px rgba(0,0,0,.12)}
        .card-photos-count{position:absolute;bottom:12px;right:12px;background:rgba(15,23,42,.78);color:#fff;padding:5px 11px;border-radius:50px;font-size:10.5px;font-weight:600;display:inline-flex;align-items:center;gap:5px;backdrop-filter:blur(8px);z-index:2}
        .card-photos-count i{color:#2dd4bf;font-size:10px}
        .card-body{padding:16px 18px 18px;display:flex;flex-direction:column;flex:1}
        .card-price{font-size:21px;font-weight:800;color:#0d9488;letter-spacing:-.3px;margin-bottom:5px}
        .card-price-cur{font-size:12px;font-weight:600;color:#94a3b8;letter-spacing:0}
        .card-location{display:flex;align-items:center;gap:6px;font-size:13px;color:#64748b;margin-bottom:10px}
        .card-location i{color:#0d9488;font-size:12px;flex-shrink:0}
        .card-desc{font-size:13px;color:#64748b;line-height:1.6;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .card-amenities{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
        .card-amenity{display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#475569;padding:4px 9px;border-radius:7px;font-size:11px;font-weight:500}
        .card-amenity i{color:#0d9488;font-size:10px}
        .card-meta{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:12px;border-top:1px solid #eef2f6}
        .card-owner{font-size:12px;color:#64748b;display:flex;align-items:center;gap:7px;font-weight:500}
        .owner-dot{width:7px;height:7px;border-radius:50%;background:#2dd4bf;box-shadow:0 0 0 3px rgba(45,212,191,.18);flex-shrink:0}
        .card-view{color:#cbd5e1;font-size:15px;transition:color .2s,transform .25s cubic-bezier(.34,1.56,.64,1)}
        .card:hover .card-view{color:#0d9488;transform:translateX(2px)}

        /* THUMBNAILS */
        .card-thumbs{display:flex;flex-wrap:wrap;gap:6px;padding:10px 12px 0;background:#fff}
        .card-thumb{width:50px;height:50px;object-fit:cover;border-radius:9px;cursor:pointer;border:2px solid transparent;transition:all .2s;background:#f1f5f9}
        .card-thumb:hover{border-color:#0d9488;transform:scale(1.08)}

        /* LIGHTBOX */
        .lb-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.92);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:24px}
        .lb-overlay.open{display:flex}
        .lb-overlay img{max-width:90vw;max-height:82vh;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
        .lb-close{position:absolute;top:20px;right:24px;background:none;border:none;color:#fff;font-size:30px;cursor:pointer;z-index:10001;transition:transform .2s}
        .lb-close:hover{transform:rotate(90deg)}
        .lb-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);border:none;color:#fff;width:48px;height:48px;border-radius:50%;font-size:20px;cursor:pointer;transition:all .2s;z-index:10001}
        .lb-nav:hover{background:rgba(255,255,255,.25)}
        .lb-prev{left:16px}
        .lb-next{right:16px}

        .empty-state{text-align:center;padding:90px 20px;grid-column:1/-1;background:#fff;border:1px dashed #d5dee8;border-radius:18px}
        .empty-state i{font-size:46px;color:#c6d2de;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:700;color:#334155;margin-bottom:8px}
        .empty-state p{color:#64748b;font-size:14px;max-width:360px;margin:0 auto;line-height:1.6}

        .load-more-wrap{text-align:center;padding:44px 0 8px}
        .load-more-wrap[hidden]{display:none}
        .load-more-wrap .btn-search{display:inline-flex;align-items:center;gap:8px;padding:13px 34px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .25s;box-shadow:0 4px 14px rgba(13,148,136,.3)}
        .load-more-wrap .btn-search:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(13,148,136,.4)}
        .load-more-spinner{font-size:24px;color:#0d9488}

        @media(max-width:900px){
            .search-form .btn-reset{display:none}
            .search-title{flex-direction:column;align-items:flex-start;gap:12px}
            .search-section{padding:38px 24px 64px}
            .search-field{flex:1 1 40%}
            .search-field+.search-field{border-left:none}
        }
        @media(max-width:768px){
            .navbar{padding:12px 16px}
            .search-section{padding:30px 16px 56px}
            .listings{padding:20px 16px 40px}
            .search-form{flex-direction:column;align-items:stretch;border-radius:16px}
            .search-field{flex:1 1 auto;min-width:0;padding:10px 14px}
            .search-field+.search-field{border-left:none;border-top:1px solid #eef2f6}
            .search-form .btn-search{justify-content:center;width:100%;min-height:50px}
            .search-title h1{font-size:23px}
            .search-quick{gap:8px}
        }
        @media(max-width:480px){
            .navbar{padding:10px 12px}
            .nav-right .btn-accent{font-size:0;padding:9px 11px}
            .nav-right .btn-accent i{font-size:15px}
            .nav-brand-text{font-size:16px}
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
                <div class="bell-wrap">
                    <button class="bell-btn" onclick="toggleNotif(this)" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if($unread_count > 0): ?><span class="bell-badge" id="bellBadge"><?php echo min($unread_count, 99); ?></span><?php endif; ?>
                    </button>
                    <div class="notif-dropdown">
                        <div class="notif-header">
                            <h4><?php echo t('notifications'); ?></h4>
                            <span class="notif-unread-count" id="notifCount"><?php echo $unread_count; ?> <?php echo t('unread'); ?></span>
                        </div>
                        <div class="notif-list">
                            <?php if(empty($notifs)): ?>
                                <div class="notif-empty"><i class="fas fa-bell-slash"></i><?php echo t('no_notifications'); ?></div>
                            <?php else: foreach($notifs as $n):
                                $nType = $n['type'] === 'listing' ? 'listing' : ($n['type'] === 'rejection' ? 'rejection' : '');
                                $nIcon = $n['type'] === 'rent_request' ? 'fa-hand-holding-heart' : ($n['type'] === 'rejection' ? 'fa-circle-xmark' : 'fa-circle-check');
                                $nLink = $n['link'] ? htmlspecialchars($n['link']) : '#';
                            ?>
                                <a href="<?php echo $nLink; ?>" class="notif-item<?php echo $n['is_read'] ? '' : ' unread'; ?>">
                                    <span class="notif-icon<?php echo $nType ? ' type-' . $nType : ''; ?>"><i class="fas <?php echo $nIcon; ?>"></i></span>
                                    <span class="notif-text">
                                        <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($n['message']); ?></p>
                                        <time><?php echo timeAgo($n['created_at']); ?></time>
                                    </span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                        <?php if(!empty($notifs)): ?>
                        <div class="notif-footer">
                            <button onclick="markAllRead()"><i class="fas fa-check-double"></i> <?php echo t('mark_all_read'); ?></button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="user-avatar-wrap">
                    <div class="user-avatar" onclick="toggleUserMenu(this)" aria-label="Account menu"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></div>
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
                <a href="login.php"><i class="fas fa-right-to-bracket"></i> Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="search-section">
        <div class="search-inner">
            <div class="search-title">
                <div class="search-title-text">
                    <div class="search-eyebrow"><i class="fas fa-house-chimney"></i> <?php echo t('browse_eyebrow'); ?></div>
                    <h1><?php echo t('find_properties'); ?></h1>
                    <p><?php echo t('browse_sub'); ?></p>
                </div>
                <span class="count"><i class="fas fa-layer-group"></i> <?php echo $total_filtered . ' ' . t('listings_count'); ?></span>
            </div>
            <form method="GET" action="index.php" class="search-form">
                <div class="search-field">
                    <label class="sf-label" for="f-cat"><i class="fas fa-tag"></i> <?php echo t('lbl_category'); ?></label>
                    <div class="sf-control">
                        <select id="f-cat" name="cat">
                            <option value=""><?php echo t('all_categories'); ?></option>
                            <optgroup label="<?php echo t('residential'); ?>">
                                <option value="Single Home" <?php if(isset($_GET['cat']) && $_GET['cat']=='Single Home') echo 'selected'; ?>><?php echo t('single_home'); ?></option>
                                <option value="Apartment" <?php if(isset($_GET['cat']) && $_GET['cat']=='Apartment') echo 'selected'; ?>><?php echo t('apartment'); ?></option>
                                <option value="Villa" <?php if(isset($_GET['cat']) && $_GET['cat']=='Villa') echo 'selected'; ?>><?php echo t('villa'); ?></option>
                            </optgroup>
                            <optgroup label="<?php echo t('commercial'); ?>">
                                <option value="Office" <?php if(isset($_GET['cat']) && $_GET['cat']=='Office') echo 'selected'; ?>><?php echo t('office'); ?></option>
                                <option value="Shop" <?php if(isset($_GET['cat']) && $_GET['cat']=='Shop') echo 'selected'; ?>><?php echo t('shop'); ?></option>
                                <option value="Warehouse" <?php if(isset($_GET['cat']) && $_GET['cat']=='Warehouse') echo 'selected'; ?>><?php echo t('warehouse'); ?></option>
                            </optgroup>
                        </select>
                        <i class="fas fa-chevron-down sf-chev"></i>
                    </div>
                </div>
                <div class="search-field">
                    <label class="sf-label" for="f-loc"><i class="fas fa-map-location-dot"></i> <?php echo t('lbl_location'); ?></label>
                    <div class="sf-control">
                        <input id="f-loc" type="text" name="kb" placeholder="<?php echo t('search_kebele'); ?>" value="<?php echo isset($_GET['kb']) ? htmlspecialchars($_GET['kb']) : ''; ?>">
                    </div>
                </div>
                <div class="search-field">
                    <label class="sf-label" for="f-price"><i class="fas fa-coins"></i> <?php echo t('lbl_price'); ?></label>
                    <div class="sf-control">
                        <input id="f-price" type="number" name="max_pr" placeholder="<?php echo t('max_price'); ?>" value="<?php echo isset($_GET['max_pr']) ? htmlspecialchars($_GET['max_pr']) : ''; ?>">
                    </div>
                </div>
                <div class="search-field">
                    <label class="sf-label" for="f-sort"><i class="fas fa-arrow-down-wide-short"></i> <?php echo t('lbl_sort'); ?></label>
                    <div class="sf-control">
                        <select id="f-sort" name="sort">
                            <option value="newest" <?php if(isset($_GET['sort']) && $_GET['sort']=='newest') echo 'selected'; ?>><?php echo t('newest_first'); ?></option>
                            <option value="price_low" <?php if(isset($_GET['sort']) && $_GET['sort']=='price_low') echo 'selected'; ?>><?php echo t('price_low'); ?></option>
                            <option value="price_high" <?php if(isset($_GET['sort']) && $_GET['sort']=='price_high') echo 'selected'; ?>><?php echo t('price_high'); ?></option>
                        </select>
                        <i class="fas fa-chevron-down sf-chev"></i>
                    </div>
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i><span><?php echo t('search_btn'); ?></span></button>
                <a href="index.php" class="btn-reset" title="<?php echo t('reset_btn'); ?>"><i class="fas fa-rotate-left"></i></a>
            </form>
            <div class="search-quick">
                <span class="sq-label"><i class="fas fa-fire"></i> <?php echo t('popular'); ?></span>
                <?php $quickCats = [['Apartment', 'apartment'], ['Villa', 'villa'], ['Single Home', 'single_home'], ['Office', 'office']]; foreach($quickCats as $qc): ?>
                <a href="<?php echo quickFilterUrl($qc[0]); ?>" class="<?php echo (isset($_GET['cat']) && $_GET['cat'] === $qc[0]) ? 'active' : ''; ?>"><i class="fas fa-house"></i> <?php echo t($qc[1]); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="listings">
        <div class="card-grid" id="listingGrid">
            <?php
            $offset = 0;
            $stmt = mysqli_prepare($conn, $q['sql'] . " LIMIT ? OFFSET ?");
            $types = $q['types'] . 'ii';
            $params = $q['params'];
            $params[] = $LISTING_LIMIT;
            $params[] = $offset;
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $rendered = 0;
            if($res && mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    renderPropertyCard($row, $all_amenities, $house_amenities, $house_images);
                    $rendered++;
                }
            } else {
                echo '<div class="empty-state"><i class="fas fa-home"></i><h3>'.t('no_properties').'</h3><p>'.t('try_adjust').'</p></div>';
            }
            ?>
        </div>
        <div id="loadMoreWrap" class="load-more-wrap" style="display:none">
            <button id="loadMoreBtn" class="btn-search" style="padding:12px 32px">
                <i class="fas fa-angle-down"></i> <?php echo t('load_more'); ?>
            </button>
            <i id="loadMoreSpinner" class="load-more-spinner fas fa-spinner fa-spin" style="display:none"></i>
        </div>
    </div>

    <div class="lb-overlay" id="lbOverlay">
        <button class="lb-close" id="lbClose" aria-label="Close"><i class="fas fa-xmark"></i></button>
        <button class="lb-nav lb-prev" id="lbPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
        <img id="lbImg" src="" alt="Property photo">
        <button class="lb-nav lb-next" id="lbNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
    </div>

    <script>
    document.querySelectorAll('.card').forEach(function(card){
        card.addEventListener('click', function(e){
            if(e.target.closest('a')) return;
            var href = card.getAttribute('data-href');
            if(href) window.location = href;
        });
    });

    document.addEventListener('click', function(e){
        var thumb = e.target.closest('.card-thumb');
        if(thumb){
            var thumbs = thumb.parentElement.querySelectorAll('.card-thumb');
            var idx = Array.prototype.indexOf.call(thumbs, thumb);
            openLightbox(thumbs, idx);
        }
    });

    var lbOverlay = document.getElementById('lbOverlay');
    var lbImg = document.getElementById('lbImg');
    var lbGroup = [];
    var lbIndex = 0;

    function openLightbox(thumbs, idx){
        lbGroup = Array.prototype.map.call(thumbs, function(t){ return t.src; });
        lbIndex = idx;
        showLbImage();
        lbOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function showLbImage(){
        lbImg.src = lbGroup[lbIndex] || '';
    }
    function closeLightbox(){
        lbOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    function lbStep(dir){
        if(!lbGroup.length) return;
        lbIndex = (lbIndex + dir + lbGroup.length) % lbGroup.length;
        showLbImage();
    }
    lbOverlay.addEventListener('click', function(e){
        if(e.target === lbOverlay || e.target.closest('.lb-close')) closeLightbox();
        if(e.target.closest('.lb-prev')) lbStep(-1);
        if(e.target.closest('.lb-next')) lbStep(1);
    });
    document.addEventListener('keydown', function(e){
        if(!lbOverlay.classList.contains('open')) return;
        if(e.key === 'Escape') closeLightbox();
        if(e.key === 'ArrowLeft') lbStep(-1);
        if(e.key === 'ArrowRight') lbStep(1);
    });

    function toggleNotif(btn){
        var wrap = btn.closest('.bell-wrap');
        var isOpen = wrap.classList.contains('open');
        document.querySelectorAll('.bell-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        document.querySelectorAll('.user-avatar-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        if(!isOpen) wrap.classList.add('open');
    }
    function toggleUserMenu(avatar){
        var wrap = avatar.closest('.user-avatar-wrap');
        var isOpen = wrap.classList.contains('open');
        document.querySelectorAll('.bell-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        document.querySelectorAll('.user-avatar-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        if(!isOpen) wrap.classList.add('open');
    }
    document.addEventListener('click', function(e){
        if(e.target.closest('.bell-wrap') || e.target.closest('.user-avatar-wrap')) return;
        document.querySelectorAll('.bell-wrap.open, .user-avatar-wrap.open').forEach(function(w){ w.classList.remove('open'); });
    });

    function markAllRead(){
        var body = new FormData();
        body.append('csrf_token', <?php echo json_encode($_SESSION['csrf_token']); ?>);
        fetch('mark_notifications_read.php', {method: 'POST', body: body}).then(function(){
            var badge = document.getElementById('bellBadge');
            if(badge) badge.remove();
            var count = document.getElementById('notifCount');
            if(count) count.textContent = '0 unread';
            document.querySelectorAll('.notif-item.unread').forEach(function(i){ i.classList.remove('unread'); });
        });
    }

    (function(){
        var grid = document.getElementById('listingGrid');
        var wrap = document.getElementById('loadMoreWrap');
        var btn = document.getElementById('loadMoreBtn');
        var spinner = document.getElementById('loadMoreSpinner');
        if(!grid || !wrap || !btn) return;
        var total = <?php echo (int)$total_filtered; ?>;
        var limit = <?php echo (int)$LISTING_LIMIT; ?>;
        var loaded = <?php echo (int)$rendered; ?>;
        if(loaded >= total) return;
        wrap.style.display = 'block';
        var isLoading = false;
        var params = new URLSearchParams(window.location.search);
        btn.addEventListener('click', function(){
            if(isLoading || loaded >= total) return;
            isLoading = true;
            btn.style.display = 'none';
            spinner.style.display = 'inline-block';
            params.set('ajax', '1');
            params.set('offset', loaded);
            params.set('limit', limit);
            fetch('index.php?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function(res){ return res.text(); })
                .then(function(html){
                    grid.insertAdjacentHTML('beforeend', html);
                    loaded = parseInt(grid.querySelectorAll('.card').length, 10);
                    isLoading = false;
                    spinner.style.display = 'none';
                    if(loaded >= total){
                        wrap.style.display = 'none';
                    } else {
                        btn.style.display = 'inline-block';
                    }
                })
                .catch(function(){
                    isLoading = false;
                    spinner.style.display = 'none';
                    btn.style.display = 'inline-block';
                });
        });
    })();
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

    <?php include('includes/footer.php'); ?>
</body>
</html>
