<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include_once('includes/ads.php');

// Only authenticated administrators may manage advertisements.
if(!admin_meets_level(1)){
    header("Location: admin_login.php");
    exit();
}

$flash = [
    'created'      => ['Advertisement created successfully.', 'green'],
    'updated'      => ['Advertisement updated successfully.', 'green'],
    'activated'    => ['Advertisement activated. It will appear on the homepage during its schedule.', 'green'],
    'deactivated'  => ['Advertisement deactivated. It has been hidden from the homepage.', 'blue'],
    'deleted'      => ['Advertisement deleted.', 'green'],
    'notfound'     => ['That advertisement was not found.', 'red'],
    'db_error'     => ['Database error — the change was not saved.', 'red'],
    'bad_image'    => ['The banner image could not be saved.', 'red'],
    'url_invalid'  => ['The target URL must be a valid http:// or https:// link.', 'red'],
    'dates_invalid' => ['Please provide a valid start and end date/time (the end must be after the start).', 'red'],
    'title_invalid' => ['Please provide an advertisement title (1-255 characters).', 'red'],
];

$errors = [];
$msg    = isset($_GET['msg'], $flash[$_GET['msg']]) ? $flash[$_GET['msg']] : null;

// ── Form defaults (overwritten by the stored ad or by a failed submit) ──
$form = [
    'title'        => '',
    'description'  => '',
    'target_url'   => '',
    'start'        => '',
    'end'          => '',
    'status'       => 'published',
];

// ── Handle state-changing actions (CSRF-checked, admin-checked above) ──
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_validate();
    $action = (string)($_POST['action'] ?? '');

    if($action === 'save'){
        $ad_id    = (int)($_POST['ad_id'] ?? 0);
        $title    = trim((string)($_POST['title'] ?? ''));
        $desc     = trim((string)($_POST['description'] ?? ''));
        $url_raw  = trim((string)($_POST['target_url'] ?? ''));
        $start_in = trim((string)($_POST['start_datetime'] ?? ''));
        $end_in   = trim((string)($_POST['end_datetime'] ?? ''));
        $status   = (string)($_POST['status'] ?? 'published');
        if(!in_array($status, ['draft', 'published', 'inactive'], true)) $status = 'draft';

        $form = [
            'title'       => $title,
            'description' => $desc,
            'target_url'  => $url_raw,
            'start'       => $start_in,
            'end'         => $end_in,
            'status'      => $status,
        ];

        $start = ads_parse_datetime($start_in);
        $end   = ads_parse_datetime($end_in);
        $safe_url = ads_safe_url($url_raw);

        if($title === '' || mb_strlen($title) > 255){
            $errors[] = 'Please provide an advertisement title (1-255 characters).';
        }
        if(mb_strlen($desc) > 2000){
            $errors[] = 'Description must be 2000 characters or fewer.';
        }
        if($url_raw !== '' && $safe_url === ''){
            $errors[] = 'The target URL must be a valid http:// or https:// link.';
        }
        if($start === false){
            $errors[] = 'Please choose a valid start date and time.';
        }
        if($end === false){
            $errors[] = 'Please choose a valid end date and time.';
        }
        if($start !== false && $end !== false && $end <= $start){
            $errors[] = 'The end date/time must be later than the start date/time.';
        }

        $image_name = '';
        $old_image  = '';
        $video_name = '';
        $old_video  = '';
        if(!$errors){
            if($ad_id > 0){
                // Editing: image/video are optional (keep the current ones).
                $stmt = mysqli_prepare($conn, "SELECT image_path, video_path FROM advertisements WHERE id=?");
                mysqli_stmt_bind_param($stmt, "i", $ad_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                if(!$row){
                    header("Location: admin_manage_ads.php?msg=notfound");
                    exit();
                }
                $old_image = (string)$row['image_path'];
                $old_video = (string)($row['video_path'] ?? '');
            }

            // Optional promo video is validated first, so a rejected video
            // can never leave an uploaded image behind as an orphan.
            $file_v = $_FILES['ad_video'] ?? null;
            $has_video = is_array($file_v) && !empty($file_v['name']) && $file_v['error'] !== UPLOAD_ERR_NO_FILE;
            if($has_video){
                $video_error = '';
                $video_name = ads_upload_video($file_v, $video_error);
                if($video_name === ''){
                    $errors[] = $video_error ?: 'The video could not be saved.';
                }
            }
        }

        if(!$errors){
            $file = $_FILES['ad_image'] ?? null;
            $has_file = is_array($file) && !empty($file['name']) && $file['error'] !== UPLOAD_ERR_NO_FILE;
            if($has_file || $ad_id === 0){
                $upload_error = '';
                $image_name = ads_upload_image($file, $upload_error);
                if($image_name === ''){
                    $errors[] = $upload_error ?: 'The banner image could not be saved.';
                }
            }
        }

        // Anything that failed after the files were already moved into uploads/
        // must discard those fresh files, otherwise a rejected form (e.g. a
        // video supplied without a banner image) leaves orphans on disk.
        if($errors){
            if($image_name !== '') ads_delete_image($image_name);
            if($video_name !== '') ads_delete_video($video_name);
        }

        if(!$errors){
            $now = ads_now();
            // What ends up stored for each media field:
            //   image: a newly uploaded file, otherwise the one already stored
            //   video: a new upload, else the stored one (or '' when removing)
            $final_image = ($image_name !== '') ? $image_name : $old_image;
            $final_video = ($video_name !== '')
                ? $video_name
                : (!empty($_POST['remove_video']) ? '' : $old_video);

            if($ad_id === 0){
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO advertisements
                        (title, description, image_path, video_path, target_url, start_datetime, end_datetime, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $creator = (int)$_SESSION['user_id'];
                mysqli_stmt_bind_param($stmt, "ssssssssiss",
                    $title, $desc, $final_image, $final_video, $safe_url, $start, $end, $status, $creator, $now, $now);
                $ok = mysqli_stmt_execute($stmt);
                if($ok){
                    header("Location: admin_manage_ads.php?msg=created");
                    exit();
                }
                if($image_name !== '') ads_delete_image($image_name);
                if($video_name !== '') ads_delete_video($video_name);
                header("Location: admin_manage_ads.php?msg=db_error");
                exit();
            } else {
                $stmt = mysqli_prepare($conn,
                    "UPDATE advertisements
                        SET title=?, description=?, image_path=?, video_path=?, target_url=?, start_datetime=?, end_datetime=?, status=?, updated_at=?
                      WHERE id=?");
                mysqli_stmt_bind_param($stmt, "sssssssssi",
                    $title, $desc, $final_image, $final_video, $safe_url, $start, $end, $status, $now, $ad_id);
                if(mysqli_stmt_execute($stmt)){
                    // Files that were replaced or removed are cleaned up only
                    // after the row actually changed.
                    if($old_image !== '' && $old_image !== $final_image) ads_delete_image($old_image);
                    if($old_video !== '' && $old_video !== $final_video) ads_delete_video($old_video);
                    header("Location: admin_manage_ads.php?msg=updated");
                    exit();
                }
                if($image_name !== '') ads_delete_image($image_name);
                if($video_name !== '') ads_delete_video($video_name);
                header("Location: admin_manage_ads.php?msg=db_error");
                exit();
            }
        }
    }

    if($action === 'toggle'){
        $ad_id = (int)($_POST['ad_id'] ?? 0);
        $stmt = mysqli_prepare($conn, "SELECT status FROM advertisements WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $ad_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if(!$row){
            header("Location: admin_manage_ads.php?msg=notfound");
            exit();
        }
        $new_status = ($row['status'] === 'published') ? 'inactive' : 'published';
        $now = ads_now();
        $stmt2 = mysqli_prepare($conn, "UPDATE advertisements SET status=?, updated_at=? WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "ssi", $new_status, $now, $ad_id);
        mysqli_stmt_execute($stmt2);
        header("Location: admin_manage_ads.php?msg=" . ($new_status === 'published' ? 'activated' : 'deactivated'));
        exit();
    }

    if($action === 'delete'){
        $ad_id = (int)($_POST['ad_id'] ?? 0);
        $stmt = mysqli_prepare($conn, "SELECT image_path, video_path FROM advertisements WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $ad_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if($row){
            $stmt2 = mysqli_prepare($conn, "DELETE FROM advertisements WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "i", $ad_id);
            mysqli_stmt_execute($stmt2);
            ads_delete_image($row['image_path']);
            ads_delete_video((string)($row['video_path'] ?? ''));
            header("Location: admin_manage_ads.php?msg=deleted");
            exit();
        }
        header("Location: admin_manage_ads.php?msg=notfound");
        exit();
    }
}

// ── Load the advertisement being edited (or after a failed submit) ─────
$ad_id = (int)($_POST['ad_id'] ?? $_GET['edit'] ?? 0);
$editing = false;
$ad = null;
if($ad_id > 0){
    $stmt = mysqli_prepare($conn, "SELECT * FROM advertisements WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $ad_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ad = $res ? mysqli_fetch_assoc($res) : null;
    if(!$ad){
        header("Location: admin_manage_ads.php?msg=notfound");
        exit();
    }
    $editing = true;
    if(empty($errors)){ // a failed submit keeps the values the admin typed
        $form = [
            'title'       => (string)$ad['title'],
            'description' => (string)$ad['description'],
            'target_url'  => (string)$ad['target_url'],
            'start'       => ads_input_datetime($ad['start_datetime']),
            'end'         => ads_input_datetime($ad['end_datetime']),
            'status'      => in_array($ad['status'], ['draft', 'published', 'inactive'], true) ? $ad['status'] : 'draft',
        ];
    }
}

// ── List (with optional status filter, evaluated on the derived status) ──
$ads = [];
$list_stmt = mysqli_prepare($conn,
    "SELECT a.*, u.full_name AS creator
       FROM advertisements a
       LEFT JOIN users u ON u.id = a.created_by
      ORDER BY a.id DESC");
if($list_stmt && mysqli_stmt_execute($list_stmt)){
    $list_res = mysqli_stmt_get_result($list_stmt);
    if($list_res){
        while($r = mysqli_fetch_assoc($list_res)){
            $r['status_key']   = ads_status_key($r['status'], $r['start_datetime'], $r['end_datetime']);
            $r['status_meta']  = ads_status_meta($r['status_key']);
            $ads[] = $r;
        }
    }
}

$status_filter = isset($_GET['status']) ? (string)$_GET['status'] : 'all';
$allowed_filters = ['all', 'active', 'scheduled', 'expired', 'draft', 'inactive'];
if(!in_array($status_filter, $allowed_filters, true)) $status_filter = 'all';
$visible_ads = ($status_filter === 'all') ? $ads : array_values(array_filter($ads, function($a) use ($status_filter){
    return $a['status_key'] === $status_filter;
}));

function ad_count($ads, $key){
    $n = 0;
    foreach($ads as $a){ if($a['status_key'] === $key) $n++; }
    return $n;
}

// ── Visitor counters (advertisement_views) ────────────────────────────
$metrics = ads_ad_metrics($conn);
$totals  = ['visitors' => 0, 'views' => 0, 'clicks' => 0, 'today' => 0];
foreach($metrics as $m){
    $totals['visitors'] += $m['visitors'];
    $totals['views']    += $m['views'];
    $totals['clicks']   += $m['clicks'];
    $totals['today']    += $m['visitors_today'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertising - EthioRent Admin</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
</head>
<body>
<div class="content">
    <style>
        .ad-st{padding:5px 11px;border-radius:20px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:5px;letter-spacing:.3px;white-space:nowrap}
        .ad-st-active{background:rgba(16,185,129,.12);color:#059669}
        .ad-st-scheduled{background:rgba(59,130,246,.12);color:#2563eb}
        .ad-st-expired{background:rgba(239,68,68,.12);color:#dc2626}
        .ad-st-inactive{background:rgba(100,116,139,.15);color:#475569}
        .ad-st-draft{background:rgba(245,158,11,.15);color:#d97706}
        .ad-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .ad-form-grid .ad-full{grid-column:1 / -1}
        input[type=datetime-local],input[type=file],input[type=url]{
            width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;
            font-family:inherit;background:#fff;color:#334155;transition:all .2s}
        input[type=datetime-local]:focus,input[type=file]:focus,input[type=url]:focus{
            outline:none;border-color:#078930;box-shadow:0 0 0 3px rgba(7,137,48,.12)}
        .ad-hint{font-size:12px;color:#94a3b8;margin-top:5px}
        .ad-thumb{width:78px;height:50px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;background:#f1f5f9;display:block}
        .ad-thumb-empty{width:78px;height:50px;border-radius:8px;border:1px dashed #cbd5e1;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px}
        .ad-current-img{display:flex;align-items:center;gap:12px;margin-top:10px;background:#f8fafc;border:1px solid #eef2f6;border-radius:10px;padding:10px 12px}
        .ad-current-img img{width:96px;height:60px;object-fit:cover;border-radius:7px}
        .ad-current-img span{font-size:12px;color:#64748b}
        .ad-current-video video{width:130px;height:74px;object-fit:cover;border-radius:7px;background:#0f172a;display:block}
        .ad-thumb-wrap{position:relative;display:inline-block}
        .ad-thumb-badge{position:absolute;bottom:4px;right:4px;background:rgba(7,137,48,.95);color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:20px;display:inline-flex;align-items:center;gap:3px;letter-spacing:.3px}
        .ad-metric{white-space:nowrap}
        .ad-metric .ad-metric-num{display:block;font-size:19px;font-weight:800;color:#0f172a;line-height:1.1}
        .ad-metric .ad-metric-lbl{display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;font-weight:700;margin-bottom:4px}
        .ad-metric small{display:block;font-size:11.5px;color:#64748b;line-height:1.7}
        .ad-metric small b{color:#0f172a}
        .ad-totals{font-size:12.5px;color:#64748b;font-weight:600;text-transform:none;letter-spacing:0}
        .ad-totals b{color:#0f172a}
        .ad-sched{font-size:13px;color:#374151;line-height:1.5;white-space:nowrap}
        .ad-sched em{display:block;font-style:normal;font-size:10.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
        .ad-actions{display:flex;gap:6px;flex-wrap:wrap}
        .ad-title-cell strong{display:block;color:#0f172a}
        .ad-title-cell small{display:block;color:#94a3b8;font-size:12px;margin-top:3px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        @media(max-width:640px){.ad-form-grid{grid-template-columns:1fr}.ad-sched{white-space:normal}}
    </style>

    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-bullhorn"></i></div>
            <div>
                <h1>Advertising</h1>
                <div class="page-sub">Create and schedule banner advertisements shown on the homepage</div>
            </div>
        </div>
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="admin_manage_ads.php" class="filter-tab <?= ($status_filter=='all')?'active':'' ?>">All (<?php echo count($ads); ?>)</a>
                <a href="admin_manage_ads.php?status=active" class="filter-tab <?= ($status_filter=='active')?'active':'' ?>">Active (<?php echo ad_count($ads, 'active'); ?>)</a>
                <a href="admin_manage_ads.php?status=scheduled" class="filter-tab <?= ($status_filter=='scheduled')?'active':'' ?>">Scheduled (<?php echo ad_count($ads, 'scheduled'); ?>)</a>
                <a href="admin_manage_ads.php?status=expired" class="filter-tab <?= ($status_filter=='expired')?'active':'' ?>">Expired (<?php echo ad_count($ads, 'expired'); ?>)</a>
                <a href="admin_manage_ads.php?status=draft" class="filter-tab <?= ($status_filter=='draft')?'active':'' ?>">Draft (<?php echo ad_count($ads, 'draft'); ?>)</a>
                <a href="admin_manage_ads.php?status=inactive" class="filter-tab <?= ($status_filter=='inactive')?'active':'' ?>">Inactive (<?php echo ad_count($ads, 'inactive'); ?>)</a>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="flash flash-<?php echo $msg[1]; ?>">
            <i class="fas fa-<?php echo $msg[1] === 'green' ? 'check-circle' : (($msg[1] === 'blue' ? 'circle-info' : 'exclamation-circle')); ?>"></i>
            <?php echo $msg[0]; ?>
        </div>
    <?php endif; ?>

    <?php if($errors): ?>
        <div class="flash flash-red">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php foreach($errors as $e){ echo htmlspecialchars($e) . '<br>'; } ?></div>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3><?php echo $editing ? 'Edit Advertisement' : 'New Advertisement'; ?></h3>
        <div class="desc">
            <?php echo $editing
                ? 'Update the advertisement below. Changes are re-checked against the server clock.'
                : 'Fill in the details below. The advertisement is shown on the homepage only while it is published and inside its schedule.'; ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="save">
            <?php if($editing): ?><input type="hidden" name="ad_id" value="<?php echo (int)$ad['id']; ?>"><?php endif; ?>

            <div class="ad-form-grid">
                <div class="ad-full form-group">
                    <label>Title *</label>
                    <input type="text" name="title" maxlength="255" required placeholder="e.g. Move into Bole — 20% off first month"
                           value="<?php echo htmlspecialchars($form['title'], ENT_QUOTES); ?>">
                </div>

                <div class="ad-full form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" maxlength="2000"
                              placeholder="Short text shown under the banner (optional)"><?php echo htmlspecialchars($form['description'], ENT_QUOTES); ?></textarea>
                </div>

                <div class="ad-full form-group">
                    <label>Target URL (optional)</label>
                    <input type="url" name="target_url" maxlength="500" placeholder="https://example.com/offer"
                           value="<?php echo htmlspecialchars($form['target_url'], ENT_QUOTES); ?>">
                    <div class="ad-hint">Where users go when they click the advertisement. Only http:// and https:// links are allowed.</div>
                </div>

                <div class="form-group">
                    <label>Starts (date &amp; time) *</label>
                    <input type="datetime-local" name="start_datetime" required
                           value="<?php echo htmlspecialchars($form['start'], ENT_QUOTES); ?>">
                </div>

                <div class="form-group">
                    <label>Expires (date &amp; time) *</label>
                    <input type="datetime-local" name="end_datetime" required
                           value="<?php echo htmlspecialchars($form['end'], ENT_QUOTES); ?>">
                </div>

                <div class="form-group">
                    <label>Banner image <?php echo $editing ? '(leave empty to keep the current one)' : '*'; ?></label>
                    <input type="file" name="ad_image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="ad-hint">JPG, PNG, WebP or GIF · max 4 MB · recommended 1200×480 or wider.</div>
                    <?php if($editing && !empty($ad['image_path'])): ?>
                        <div class="ad-current-img">
                            <img src="uploads/<?php echo htmlspecialchars(basename($ad['image_path']), ENT_QUOTES); ?>" alt="Current banner">
                            <span>Current banner image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Promo video <span style="font-weight:500;color:#94a3b8">(optional<?php echo $editing ? ' — leave empty to keep the current one' : ''; ?>)</span></label>
                    <input type="file" name="ad_video" accept="video/mp4,video/webm,video/ogg">
                    <div class="ad-hint">MP4, WebM, OGG or MOV · max 20 MB · played silently inside the card, with sound on demand.</div>
                    <?php if($editing && !empty($ad['video_path'])): ?>
                        <div class="ad-current-img ad-current-video">
                            <video src="uploads/<?php echo htmlspecialchars(basename($ad['video_path']), ENT_QUOTES); ?>" muted playsinline controls preload="metadata"></video>
                            <span>
                                Current promo video
                                <label style="display:block;margin-top:6px;font-size:12px;color:#dc2626;cursor:pointer">
                                    <input type="checkbox" name="remove_video" value="1"> Remove this video
                                </label>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="published"   <?= $form['status']==='published'   ? 'selected' : '' ?>>Published — show on schedule</option>
                        <option value="draft"       <?= $form['status']==='draft'       ? 'selected' : '' ?>>Draft — saved but never shown</option>
                        <option value="inactive"    <?= $form['status']==='inactive'    ? 'selected' : '' ?>>Inactive — deactivated (hidden)</option>
                    </select>
                    <div class="ad-hint">Published ads show automatically between the start and end times above.</div>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editing ? 'Save Changes' : 'Create Advertisement'; ?></button>
                <a href="admin_manage_ads.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <div class="section-title">
        <i class="fas fa-layer-group"></i> All Advertisements
        <span class="ad-totals" style="float:right;font-weight:600">
            Visitors: <b><?php echo number_format($totals['visitors']); ?></b>
            · Views: <b><?php echo number_format($totals['views']); ?></b>
            · Today: <b><?php echo number_format($totals['today']); ?></b>
            · Clicks: <b><?php echo number_format($totals['clicks']); ?></b>
        </span>
    </div>

    <?php if(count($visible_ads) > 0): ?>
    <div class="data-card">
        <table>
            <thead>
                <tr>
                    <th>Banner</th>
                    <th>Advertisement</th>
                    <th>Status</th>
                    <th>Schedule</th>
                    <th>Visitors</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($visible_ads as $row):
                    $skey  = $row['status_key'];
                    $slabel = $row['status_meta'][0];
                    $sclass = $row['status_meta'][1];
                    $img = basename((string)$row['image_path']);
                    $vid = basename((string)($row['video_path'] ?? ''));
                    $url = ads_safe_url($row['target_url'] ?? '');
                    $mt  = $metrics[(int)$row['id']] ?? ['visitors'=>0, 'visitors_today'=>0, 'views'=>0, 'clicks'=>0];
                ?>
                <tr>
                    <td>
                        <span class="ad-thumb-wrap">
                            <?php if($img !== ''): ?>
                                <img class="ad-thumb" src="uploads/<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="ad-thumb-empty"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                            <?php if($vid !== ''): ?>
                                <span class="ad-thumb-badge"><i class="fas fa-circle-play"></i> Video</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="ad-title-cell">
                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                        <small><?php echo $row['description'] !== '' && $row['description'] !== null
                            ? htmlspecialchars(mb_strimwidth($row['description'], 0, 90, '…'))
                            : '<span style="color:#cbd5e1">No description</span>'; ?></small>
                        <?php if($url !== ''): ?>
                            <a href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:#078930;font-weight:600;text-decoration:none">
                                <i class="fas fa-arrow-up-right-from-square" style="font-size:10px"></i> <?php echo htmlspecialchars($url); ?>
                            </a>
                        <?php endif; ?>
                        <?php if(!empty($row['creator'])): ?>
                            <small style="color:#94a3b8">by <?php echo htmlspecialchars($row['creator']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="<?php echo $sclass; ?>"><i class="fas fa-circle" style="font-size:6px"></i> <?php echo $slabel; ?></span></td>
                    <td>
                        <div class="ad-sched">
                            <em>Starts</em><?php echo ads_format_datetime($row['start_datetime']); ?>
                            <em style="margin-top:6px">Expires</em><?php echo ads_format_datetime($row['end_datetime']); ?>
                        </div>
                    </td>
                    <td class="ad-metric">
                        <span class="ad-metric-num"><?php echo number_format($mt['visitors']); ?></span>
                        <span class="ad-metric-lbl">Visitors</span>
                        <small><b><?php echo number_format($mt['views']); ?></b> views · <b><?php echo number_format($mt['clicks']); ?></b> clicks</small>
                        <small>Today: <b><?php echo number_format($mt['visitors_today']); ?></b></small>
                    </td>
                    <td>
                        <div class="ad-actions">
                            <a href="admin_manage_ads.php?edit=<?php echo (int)$row['id']; ?>" class="btn-icon btn-icon-edit" title="Edit"><i class="fas fa-edit"></i></a>

                            <form action="admin_manage_ads.php" method="POST" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$row['id']; ?>">
                                <?php if($row['status'] === 'published'): ?>
                                    <button type="submit" class="btn-icon btn-icon-revoke" title="Deactivate (hide from homepage)"><i class="fas fa-pause"></i></button>
                                <?php else: ?>
                                    <button type="submit" class="btn-icon btn-icon-promote" title="Activate (show on schedule)"><i class="fas fa-play"></i></button>
                                <?php endif; ?>
                            </form>

                            <form action="admin_manage_ads.php" method="POST" style="display:inline" id="del-ad-<?php echo (int)$row['id']; ?>">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$row['id']; ?>">
                                <button type="button" class="btn-icon btn-icon-del" title="Delete" onclick="confirmAdDelete(<?php echo (int)$row['id']; ?>)"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif(count($ads) > 0): ?>
        <div class="empty-state">
            <i class="fas fa-filter-circle-xmark"></i>
            <h3>No advertisements match this filter</h3>
            <p>There <?php echo count($ads) === 1 ? 'is 1 advertisement' : 'are ' . count($ads) . ' advertisements'; ?> overall — choose another status tab or show all.</p>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <h3>No advertisements yet</h3>
            <p>Create your first advertisement above. It will appear on the homepage automatically during its schedule.</p>
        </div>
    <?php endif; ?>

    <div class="info-bar">
        <div class="info-icon"><i class="fas fa-clock"></i></div>
        <div>
            <strong>Schedule &amp; time zone:</strong> all start/end times are saved and compared in
            <strong><?php echo AD_TZ_LABEL; ?></strong> using the server clock (currently
            <strong><?php echo ads_format_datetime(ads_now()); ?></strong>) — never the browser's clock.
            An advertisement is visible only when it is <em>published</em>, the current server time is
            <em>at or after</em> its start, and <em>before</em> its end; scheduled ads switch on by
            themselves, expired ads switch off by themselves, and deactivated or deleted ads disappear
            immediately. Dates display as <strong>Mmm d, YYYY · HH:MM</strong> (24-hour).
        </div>
    </div>

    <div class="info-bar">
        <div class="info-icon"><i class="fas fa-chart-line"></i></div>
        <div>
            <strong>Visitor counting:</strong> each advertisement card on the homepage reports one
            <em>view</em> once it has stayed at least half visible for a second (a refresh is not counted
            twice on the same day), plus a <em>click</em> whenever the card or its link is opened.
            <strong>Visitors</strong> counts distinct people, <strong>views</strong> counts counted
            impressions and <strong>clicks</strong> counts link/CTA clicks. Only advertisements that are
            live at that moment can be counted, and the visitor key is a salted one-way hash — the raw IP
            address is never stored. Deleting an advertisement also deletes its statistics.
        </div>
    </div>
</div>
</body>
</html>
