<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/lang.php');
include('includes/security.php');
if(!isset($_SESSION['csrf_token'])) csrf_token();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$house_id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM houses WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $house_id, $user_id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$data){
    header("Location: manage_houses.php");
    exit();
}

$gallery = [];
$gstmt = mysqli_prepare($conn, "SELECT * FROM house_images WHERE house_id=? ORDER BY sort_order ASC, id ASC");
mysqli_stmt_bind_param($gstmt, "i", $house_id);
mysqli_stmt_execute($gstmt);
$gq = mysqli_stmt_get_result($gstmt);
if($gq){ while($g = mysqli_fetch_assoc($gq)) $gallery[] = $g; }

$current_amenities = [];
$astmt = mysqli_prepare($conn, "SELECT amenity_id FROM house_amenities WHERE house_id=?");
mysqli_stmt_bind_param($astmt, "i", $house_id);
mysqli_stmt_execute($astmt);
$aq = mysqli_stmt_get_result($astmt);
if($aq){ while($a = mysqli_fetch_assoc($aq)) $current_amenities[$a['amenity_id']] = $a['amenity_id']; }

$form = $data;

$toast_error = null;
$updated = false;

function process_upload($file, $upload_dir, $allowed){
    if($file['error'] !== UPLOAD_ERR_OK){
        $err_msg = match($file['error']){
            UPLOAD_ERR_INI_SIZE   => t('ph_err_ini_size'),
            UPLOAD_ERR_FORM_SIZE  => t('ph_err_form_size'),
            UPLOAD_ERR_PARTIAL    => t('ph_err_partial'),
            UPLOAD_ERR_NO_TMP_DIR => t('ph_err_no_tmp'),
            UPLOAD_ERR_CANT_WRITE => t('ph_err_cant_write'),
            UPLOAD_ERR_EXTENSION  => t('ph_err_extension'),
            default               => t('ph_err_unknown') . ' (code: ' . $file['error'] . ').'
        };
        return [false, $err_msg];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed, true)){
        return [false, t('ph_err_bad_type')];
    }
    $fname  = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target = $upload_dir . '/' . $fname;
    if(!move_uploaded_file($file['tmp_name'], $target)){
        return [false, t('ph_err_save_file')];
    }
    if(in_array($ext, ['heic', 'heif'], true)){
        $jpgName = preg_replace('/\.(heic|heif)$/i', '', $fname) . '.jpg';
        $out = shell_exec("/usr/bin/sips -s format jpeg " . escapeshellarg($target) . " --out " . escapeshellarg($upload_dir . '/' . $jpgName) . " 2>&1");
        if($out !== null && file_exists($upload_dir . '/' . $jpgName) && filesize($upload_dir . '/' . $jpgName) > 0){
            @unlink($target);
            $fname = $jpgName;
        } else {
            @unlink($target);
            return [false, t('ph_err_heic')];
        }
    }
    return [true, $fname];
}

if(isset($_POST['update'])){
    csrf_validate();
    $upload_dir = __DIR__ . '/uploads';
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $kebele   = isset($_POST['kebele'])   ? trim($_POST['kebele'])   : '';
    $street   = isset($_POST['street'])   ? trim($_POST['street'])   : '';
    $h_num    = isset($_POST['house_num']) ? trim($_POST['house_num']) : '';
    $category = isset($_POST['category']) ? $_POST['category']         : '';
    $amount   = isset($_POST['amount'])   ? (int)$_POST['amount']                                      : 0;
    $phone    = isset($_POST['phone'])    ? trim($_POST['phone'])    : '';
    $map      = isset($_POST['map_link']) ? trim($_POST['map_link']) : '';
    $desc     = isset($_POST['desc'])     ? $_POST['desc']           : '';

    $form['kebele'] = $kebele;
    $form['street'] = $street;
    $form['house_number'] = $h_num;
    $form['category'] = $category;
    $form['amount'] = $amount;
    $form['phone'] = $phone;
    $form['map_link'] = $map;
    $form['description'] = $desc;

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'];
    $created_files = [];
    $error = null;

    if($kebele === '' || $street === '' || $category === '' || $amount < 1 || $phone === ''){
        $error = t('eh_err_fill');
    } else {
        $imgName = $data['image'];

        if(!empty($_FILES['house_image']['name'])){
            list($ok, $res) = process_upload($_FILES['house_image'], $upload_dir, $allowed);
            if($ok){
                $imgName = $res;
                $created_files[] = $res;
            } else {
                $error = $res;
            }
        }

        $newGallery = [];
        if(!$error && !empty($_FILES['house_photos']['name'][0])){
            $photos = $_FILES['house_photos'];
            $total  = count($photos['name']);
            for($i = 0; $i < $total; $i++){
                if(empty($photos['name'][$i])) continue;
                $one = [
                    'name'     => $photos['name'][$i],
                    'tmp_name' => $photos['tmp_name'][$i],
                    'error'    => $photos['error'][$i],
                ];
                list($ok, $res) = process_upload($one, $upload_dir, $allowed);
                if(!$ok){ $error = $res; break; }
                $newGallery[] = $res;
                $created_files[] = $res;
                if(count($newGallery) >= 6) break;
            }
        }

        if(!$error){
            $was_approved = isset($data['is_approved']) && (int)$data['is_approved'] === 1;
            if(!$was_approved){
                $prev_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM requests WHERE house_id=? AND status=1");
                mysqli_stmt_bind_param($prev_stmt, "i", $house_id);
                mysqli_stmt_execute($prev_stmt);
                $prev_res = mysqli_stmt_get_result($prev_stmt);
                if($prev_res && ($c = mysqli_fetch_row($prev_res)) && (int)$c[0] > 0) $was_approved = true;
            }
            $changes_json = '';
            if($was_approved){
                $labels = [
                    'kebele'       => t('ph_kebele'),
                    'street'       => t('ph_street'),
                    'house_number' => t('ph_house_number'),
                    'category'     => t('ph_category'),
                    'amount'       => t('ph_monthly_price'),
                    'phone'        => t('ph_contact_phone'),
                    'map_link'     => t('ph_map_link'),
                    'description'  => t('ph_description'),
                ];
                $field_sets = [
                    'kebele'       => [(string)($data['kebele'] ?? ''), $kebele],
                    'street'       => [(string)($data['street'] ?? ''), $street],
                    'house_number' => [(string)($data['house_number'] ?? ''), $h_num],
                    'category'     => [(string)($data['category'] ?? ''), $category],
                    'amount'       => [(string)($data['amount'] ?? 0), (string)$amount],
                    'phone'        => [(string)($data['phone'] ?? ''), $phone],
                    'map_link'     => [(string)($data['map_link'] ?? ''), $map],
                    'description'  => [(string)($data['description'] ?? ''), $desc],
                ];
                $changes = [];
                foreach($field_sets as $key => $pair){
                    if($pair[0] !== $pair[1]){
                        $changes[] = ['field' => $labels[$key], 'from' => $pair[0], 'to' => $pair[1]];
                    }
                }

                if($imgName !== ($data['image'] ?? '') && !empty($data['image'])){
                    $changes[] = ['field' => t('eh_cover_photo'), 'from' => basename($data['image']), 'to' => basename($imgName)];
                }

                $old_gallery_files = [];
                foreach($gallery as $g) $old_gallery_files[] = $g['filename'];
                $remove_imgs = isset($_POST['remove_img']) && is_array($_POST['remove_img']) ? array_map('basename', $_POST['remove_img']) : [];
                $new_gallery_files = array_values(array_diff($old_gallery_files, $remove_imgs));
                foreach($newGallery as $fn) $new_gallery_files[] = $fn;
                $g_added   = array_values(array_diff($new_gallery_files, $old_gallery_files));
                $g_removed = array_values(array_diff($old_gallery_files, $new_gallery_files));
                if($g_added || $g_removed){
                    $changes[] = ['field' => t('eh_gallery'), 'from' => count($old_gallery_files), 'to' => count($new_gallery_files), 'added' => $g_added, 'removed' => $g_removed];
                }

                $old_amen = array_keys($current_amenities);
                $new_amen = [];
                if(isset($_POST['amenities']) && is_array($_POST['amenities'])){
                    foreach($_POST['amenities'] as $aid){
                        $aid = (int)$aid;
                        if($aid > 0 && !isset($new_amen[$aid])) $new_amen[$aid] = $aid;
                    }
                }
                $new_amen = array_values($new_amen);
                $am_added   = array_values(array_diff($new_amen, $old_amen));
                $am_removed = array_values(array_diff($old_amen, $new_amen));
                if($am_added || $am_removed){
                    $changes[] = ['field' => t('ph_amenities'), 'added' => $am_added, 'removed' => $am_removed];
                }

                $changes_json = json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $update_stmt = mysqli_prepare($conn, "UPDATE houses SET kebele=?, street=?, house_number=?, category=?, amount=?, phone=?, map_link=?, description=?, image=?, status='Pending', is_approved=0 WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($update_stmt, "ssssissssii", $kebele, $street, $h_num, $category, $amount, $phone, $map, $desc, $imgName, $house_id, $user_id);

            if(mysqli_stmt_execute($update_stmt)){
                $req_type = $was_approved ? 'edit' : 'new';
                $changes_val = ($changes_json === '' || $changes_json === '[]') ? null : $changes_json;
                $req_stmt = mysqli_prepare($conn, "INSERT INTO requests (user_id, house_id, status, type, changes, created_at) VALUES (?, ?, 0, ?, ?, NOW())");
                mysqli_stmt_bind_param($req_stmt, "iiss", $user_id, $house_id, $req_type, $changes_val);
                mysqli_stmt_execute($req_stmt);

                if($imgName !== $data['image'] && !empty($data['image'])){
                    @unlink($upload_dir . '/' . basename($data['image']));
                }

                if(isset($_POST['remove_img']) && is_array($_POST['remove_img'])){
                    $del_img_stmt = mysqli_prepare($conn, "DELETE FROM house_images WHERE house_id=? AND filename=?");
                    foreach($_POST['remove_img'] as $rf){
                        $rf = basename($rf);
                        if(!$rf || $rf === $imgName) continue;
                        @unlink($upload_dir . '/' . $rf);
                        mysqli_stmt_bind_param($del_img_stmt, "is", $house_id, $rf);
                        mysqli_stmt_execute($del_img_stmt);
                    }
                }

                $next_order = 0;
                $gq2 = mysqli_prepare($conn, "SELECT MAX(sort_order) m FROM house_images WHERE house_id=?");
                mysqli_stmt_bind_param($gq2, "i", $house_id);
                mysqli_stmt_execute($gq2);
                $gq2_res = mysqli_stmt_get_result($gq2);
                if($gq2_res && $row2 = mysqli_fetch_assoc($gq2_res)) $next_order = (int)$row2['m'] + 1;
                
                $ins_img_stmt = mysqli_prepare($conn, "INSERT INTO house_images (house_id, filename, sort_order) VALUES (?, ?, ?)");
                foreach($newGallery as $fn){
                    mysqli_stmt_bind_param($ins_img_stmt, "isi", $house_id, $fn, $next_order);
                    mysqli_stmt_execute($ins_img_stmt);
                    $next_order++;
                }

                $del_am_stmt = mysqli_prepare($conn, "DELETE FROM house_amenities WHERE house_id=?");
                mysqli_stmt_bind_param($del_am_stmt, "i", $house_id);
                mysqli_stmt_execute($del_am_stmt);
                
                if(isset($_POST['amenities']) && is_array($_POST['amenities'])){
                    $ins_am_stmt = mysqli_prepare($conn, "INSERT IGNORE INTO house_amenities (house_id, amenity_id) VALUES (?, ?)");
                    foreach($_POST['amenities'] as $aid){
                        $aid = (int)$aid;
                        if($aid > 0) {
                            mysqli_stmt_bind_param($ins_am_stmt, "ii", $house_id, $aid);
                            mysqli_stmt_execute($ins_am_stmt);
                        }
                    }
                }

                $updated = true;
            } else {
                $error = t('eh_db_error');
            }
        }
    }

    if($error){
        foreach($created_files as $f){ @unlink($upload_dir . '/' . $f); }
        if(isset($_POST['amenities']) && is_array($_POST['amenities'])){
            $current_amenities = [];
            foreach($_POST['amenities'] as $aid){
                $aid = (int)$aid;
                if($aid > 0) $current_amenities[$aid] = $aid;
            }
        }
        $toast_error = $error;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('eh_title')); ?> - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}

        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:all .2s}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
        .nav-right .btn-accent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
        .user-avatar-wrap{position:relative}
        .user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;cursor:pointer;border:2px solid rgba(255,255,255,.2);transition:all .2s}
        .user-avatar:hover{border-color:rgba(255,255,255,.5);transform:scale(1.05)}
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;width:220px;background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s;z-index:1001}
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

        .form-page{max-width:760px;margin:40px auto;padding:0 20px}
        .form-header{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:28px;flex-wrap:wrap}
        .form-header h1{font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
        .form-header p{color:#64748b;font-size:14px;margin-top:4px}
        .form-header .head-actions{display:flex;gap:10px}
        .form-header .view-link{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1.5px solid #e5e7eb;color:#475569;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s}
        .form-header .view-link:hover{border-color:#0d9488;color:#0d9488}
        .form-header .view-link .status-pill{margin-left:6px;background:#fef3c7;color:#b45309;padding:2px 8px;border-radius:50px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}

        .form-card{background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .form-section{margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9}
        .form-section:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0}
        .form-section-title{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px}
        .form-section-title i{color:#0d9488;font-size:16px}
        .form-section-title .badge{margin-left:auto;font-size:11px;font-weight:700;color:#64748b;background:#f1f5f9;padding:3px 10px;border-radius:50px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:16px}
        .form-group:last-child{margin-bottom:0}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group label .req{color:#dc2626}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s;background:#fff;color:#1e293b}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.08)}
        .form-group textarea{resize:vertical;min-height:110px}
        .form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
        .form-group .map-auto{background:rgba(13,148,136,.06);color:#0d9488;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;display:none;margin-top:8px}
        .form-group .map-auto i{margin-right:4px}
        .map-toggle{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:#475569;cursor:pointer;margin-top:8px;user-select:none}
        .map-toggle input[type="checkbox"]{width:16px;height:16px;accent-color:#0d9488;cursor:pointer}

        .form-group label .badge{margin-left:6px;font-size:10px;font-weight:700;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:50px;vertical-align:middle}
        .amenity-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
        .amenity-item{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all .2s;background:#fff;font-size:13px;font-weight:500;color:#475569;user-select:none}
        .amenity-item:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .amenity-item input{display:none}
        .amenity-item.checked{border-color:#0d9488;background:rgba(13,148,136,.06);color:#0d9488}
        .amenity-item .amenity-icon{width:18px;text-align:center;font-size:13px;color:#94a3b8;transition:color .2s}
        .amenity-item.checked .amenity-icon{color:#0d9488}

        .cover-block{display:flex;align-items:center;gap:20px}
        .cover-preview{width:180px;height:130px;border-radius:12px;overflow:hidden;border:2px solid #f1f5f9;background:#f1f5f9;flex-shrink:0;position:relative}
        .cover-preview img{width:100%;height:100%;object-fit:cover}
        .cover-preview .cover-tag{position:absolute;left:8px;top:8px;background:rgba(13,148,136,.92);color:#fff;font-size:10px;font-weight:700;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px}
        .cover-actions{flex:1;min-width:0}
        .cover-actions .file-upload{border:2px dashed #e5e7eb;border-radius:12px;padding:18px;text-align:center;cursor:pointer;transition:all .3s;background:#fafbfc;margin-bottom:8px}
        .cover-actions .file-upload:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .cover-actions .file-upload i{font-size:22px;color:#94a3b8;margin-bottom:4px;display:block}
        .cover-actions .file-upload p{font-size:13px;color:#64748b;font-weight:500}
        .cover-actions .file-upload input{display:none}
        .file-name{font-size:12px;color:#0d9488;font-weight:600;display:none}

        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px}
        .gallery-item{position:relative;border-radius:12px;overflow:hidden;border:2px solid #f1f5f9;aspect-ratio:4/3;background:#f1f5f9}
        .gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
        .gallery-item .remove-btn{position:absolute;top:6px;right:6px;width:26px;height:26px;border-radius:50%;background:rgba(15,23,42,.65);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .2s;z-index:2}
        .gallery-item .remove-btn:hover{background:#ef4444;transform:scale(1.1)}
        .gallery-item.removing{border-color:#ef4444;opacity:.55}
        .gallery-item.removing .remove-btn{background:#ef4444}
        .gallery-item.removing img{filter:grayscale(1)}
        .gallery-item input[type="checkbox"]{display:none}
        .gallery-item .remove-tag{position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:rgba(239,68,68,.55);color:#fff;font-size:12px;font-weight:700}
        .gallery-item.removing .remove-tag{display:flex}

        .gallery-add{border:2px dashed #e5e7eb;border-radius:12px;padding:22px;text-align:center;cursor:pointer;transition:all .3s;background:#fafbfc;color:#64748b;font-size:13px;font-weight:500}
        .gallery-add:hover{border-color:#0d9488;background:rgba(13,148,136,.03);color:#0d9488}
        .gallery-add i{font-size:20px;display:block;margin-bottom:4px}
        .gallery-add input{display:none}

        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}

        .btn-row{display:grid;grid-template-columns:1fr auto;gap:12px;margin-top:28px;padding-top:24px;border-top:1px solid #f1f5f9}
        .btn-save{padding:14px 24px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-save:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .btn-cancel{padding:14px 20px;background:#f1f5f9;color:#475569;border:none;border-radius:12px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-cancel:hover{background:#e2e8f0}

        /* SUCCESS MODAL */
        .ph-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:20px;animation:phFade .25s ease}
        .ph-overlay.ph-active{display:flex}
        @keyframes phFade{from{opacity:0}to{opacity:1}}
        .ph-card{background:#fff;border-radius:20px;max-width:430px;width:100%;padding:38px 34px;box-shadow:0 25px 60px rgba(0,0,0,.3);text-align:center;animation:phPop .35s cubic-bezier(.34,1.56,.64,1)}
        @keyframes phPop{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
        .ph-icon{width:76px;height:76px;margin:0 auto 20px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;box-shadow:0 10px 25px rgba(16,185,129,.35)}
        .ph-card h2{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:10px;letter-spacing:-.4px}
        .ph-card p{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:26px}
        .ph-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .ph-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 16px;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;transition:all .25s;border:none;font-family:inherit;text-decoration:none}
        .ph-btn-primary{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
        .ph-btn-primary:hover{box-shadow:0 6px 18px rgba(13,148,136,.4);transform:translateY(-1px)}
        .ph-btn-ghost{background:#f1f5f9;color:#334155}
        .ph-btn-ghost:hover{background:#e2e8f0}

        @media(max-width:640px){
            .form-row{grid-template-columns:1fr}
            .form-page{padding:0 12px;margin:20px auto}
            .form-card{padding:20px}
            .cover-block{flex-direction:column;align-items:stretch}
            .cover-preview{width:100%;height:170px}
            .btn-row{grid-template-columns:1fr}
        }
        @media(max-width:480px){
            .navbar{padding:11px 12px}
            .ph-actions{grid-template-columns:1fr}
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
            <a href="index.php"><i class="fas fa-search"></i> <?php echo t('nav_browse'); ?></a>
            <a href="post_house.php" class="btn-accent"><i class="fas fa-plus"></i> <?php echo t('eh_new_post'); ?></a>
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
        </div>
    </nav>

    <div class="form-page">
        <div class="form-header">
            <div>
                <h1><?php echo t('eh_title'); ?></h1>
                <p><?php echo t('eh_page_sub'); ?></p>
            </div>
            <div class="head-actions">
                <a class="view-link" href="house_detail.php?house=<?php echo (int)$house_id; ?>"><i class="fas fa-external-link"></i> <?php echo t('eh_preview'); ?>
                    <span class="status-pill"><?php echo htmlspecialchars(t_status($data['status'] ?? 'Pending')); ?></span>
                </a>
            </div>
        </div>

        <div class="form-card">
            <?php if(isset($toast_error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($toast_error); ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <!-- Location -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-location-dot"></i> <?php echo t('ph_location'); ?></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo t('ph_kebele'); ?> <span class="req">*</span></label>
                            <input type="text" name="kebele" placeholder="<?php echo t('ph_kebele_ph'); ?>" value="<?php echo htmlspecialchars($form['kebele']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo t('ph_house_number'); ?></label>
                            <input type="text" name="house_num" placeholder="<?php echo t('ph_house_number_ph'); ?>" value="<?php echo htmlspecialchars($form['house_number'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('ph_street'); ?> <span class="req">*</span></label>
                        <input type="text" name="street" placeholder="<?php echo t('ph_street_ph'); ?>" value="<?php echo htmlspecialchars($form['street']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('ph_map_link'); ?></label>
                        <input type="url" id="map_link" name="map_link" placeholder="<?php echo t('ph_map_ph'); ?>" value="<?php echo htmlspecialchars($form['map_link'] ?? ''); ?>">
                        <label class="map-toggle"><input type="checkbox" id="map_auto"> <?php echo t('ph_map_auto'); ?></label>
                        <div class="map-auto" id="auto-gen-note"><i class="fas fa-check-circle"></i> <?php echo t('ph_map_auto_note'); ?></div>
                    </div>
                </div>

                <!-- Property Info -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-home"></i> <?php echo t('ph_property_info'); ?></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo t('ph_category'); ?> <span class="req">*</span></label>
                            <select name="category" required>
                                <option value=""><?php echo t('ph_select_type'); ?></option>
                                <optgroup label="<?php echo t('residential'); ?>">
                                    <option value="Single Home" <?php echo $form['category'] === 'Single Home' ? 'selected' : ''; ?>><?php echo t('single_home'); ?></option>
                                    <option value="Apartment" <?php echo $form['category'] === 'Apartment' ? 'selected' : ''; ?>><?php echo t('apartment'); ?></option>
                                    <option value="Villa" <?php echo $form['category'] === 'Villa' ? 'selected' : ''; ?>><?php echo t('villa'); ?></option>
                                </optgroup>
                                <optgroup label="<?php echo t('commercial'); ?>">
                                    <option value="Office" <?php echo $form['category'] === 'Office' ? 'selected' : ''; ?>><?php echo t('office'); ?></option>
                                    <option value="Shop" <?php echo $form['category'] === 'Shop' ? 'selected' : ''; ?>><?php echo t('shop'); ?></option>
                                    <option value="Warehouse" <?php echo $form['category'] === 'Warehouse' ? 'selected' : ''; ?>><?php echo t('warehouse'); ?></option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo t('ph_monthly_price'); ?> <span class="req">*</span></label>
                            <input type="number" name="amount" placeholder="<?php echo t('ph_price_ph'); ?>" value="<?php echo htmlspecialchars($form['amount']); ?>" required min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('ph_contact_phone'); ?> <span class="req">*</span></label>
                        <input type="text" name="phone" placeholder="<?php echo t('ph_contact_phone_ph'); ?>" value="<?php echo htmlspecialchars($form['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('ph_description'); ?></label>
                        <textarea name="desc" placeholder="<?php echo t('ph_desc_ph'); ?>"><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Photos -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-camera"></i> <?php echo t('ph_photos'); ?></div>

                    <div class="cover-block" style="margin-bottom:20px">
                        <div class="cover-preview">
                            <span class="cover-tag"><?php echo t('eh_cover'); ?></span>
                            <img src="uploads/<?php echo htmlspecialchars($data['image']); ?>" alt="<?php echo htmlspecialchars(t('eh_cover_photo')); ?>">
                        </div>
                        <div class="cover-actions">
                            <div class="file-upload" onclick="this.querySelector('input').click()">
                                <i class="fas fa-camera-retro"></i>
                                <p><?php echo t('eh_change_cover'); ?></p>
                                <div class="file-name" id="cover-name"></div>
                                <input type="file" name="house_image" accept="image/*" onchange="updatePhotoList(this, 'cover-name')">
                            </div>
                            <div class="hint"><?php echo t('eh_cover_hint'); ?></div>
                        </div>
                    </div>

                    <?php if(!empty($gallery)): ?>
                    <div class="form-group" style="margin-bottom:14px">
                        <label><?php echo t('eh_gallery'); ?> <span class="badge"><?php echo count($gallery); ?></span></label>
                        <div class="gallery-grid">
                            <?php foreach($gallery as $img): ?>
                            <div class="gallery-item" data-toggle="gallery">
                                <img src="uploads/<?php echo htmlspecialchars($img['filename']); ?>" alt="<?php echo htmlspecialchars(t('eh_gallery')); ?>">
                                <button type="button" class="remove-btn" title="<?php echo htmlspecialchars(t('eh_remove_photo')); ?>"><i class="fas fa-xmark"></i></button>
                                <input type="checkbox" name="remove_img[]" value="<?php echo htmlspecialchars($img['filename']); ?>">
                                <span class="remove-tag"><i class="fas fa-trash"></i> <?php echo t('eh_remove'); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="hint"><?php echo t('eh_remove_hint'); ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><?php echo t('eh_add_more'); ?></label>
                        <div class="gallery-add" onclick="this.querySelector('input').click()">
                            <i class="fas fa-image"></i>
                            <?php echo t('eh_add_photos'); ?>
                            <div class="file-name" id="gallery-name"></div>
                            <input type="file" name="house_photos[]" accept="image/*" multiple onchange="updatePhotoList(this, 'gallery-name')">
                        </div>
                        <div class="hint"><?php echo t('eh_add_hint'); ?></div>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-star"></i> <?php echo t('ph_amenities'); ?></div>
                    <p style="font-size:13px;color:#94a3b8;margin-bottom:14px"><?php echo t('ph_amenities_hint'); ?></p>
                    <div class="amenity-grid">
                        <?php
                        $amenities_result = mysqli_query($conn, "SELECT * FROM amenities ORDER BY sort_order ASC");
                        while($amenity = mysqli_fetch_assoc($amenities_result)):
                        ?>
                        <label class="amenity-item<?php echo isset($current_amenities[$amenity['id']]) ? ' checked' : ''; ?>">
                            <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" <?php echo isset($current_amenities[$amenity['id']]) ? 'checked' : ''; ?>>
                            <span class="amenity-icon"><i class="<?php echo htmlspecialchars($amenity['icon']); ?>"></i></span>
                            <?php echo htmlspecialchars($amenity['name']); ?>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" name="update" class="btn-save"><i class="fas fa-save"></i> <?php echo t('eh_save'); ?></button>
                    <a href="manage_houses.php" class="btn-cancel"><?php echo t('eh_cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>

    <script>
    var ehPhotosLabel = <?php echo json_encode(t('ph_photos_plural')); ?>;
    var ehPhotoLabel = <?php echo json_encode(t('ph_photo')); ?>;
    var ehSelectedLabel = <?php echo json_encode(t('ph_selected')); ?>;
    var ehUndoRemove = <?php echo json_encode(t('eh_undo_remove')); ?>;
    var ehRemovePhoto = <?php echo json_encode(t('eh_remove_photo')); ?>;
    document.querySelectorAll('.amenity-item input[type="checkbox"]').forEach(function(cb){
        cb.addEventListener('change', function(){
            this.closest('.amenity-item').classList.toggle('checked', this.checked);
        });
    });

    document.querySelectorAll('.gallery-item').forEach(function(item){
        var btn = item.querySelector('.remove-btn');
        var box = item.querySelector('input[type="checkbox"]');
        function applyState(){
            var removing = box.checked;
            item.classList.toggle('removing', removing);
            btn.innerHTML = removing ? '<i class="fas fa-rotate-left"></i>' : '<i class="fas fa-xmark"></i>';
            btn.title = removing ? ehUndoRemove : ehRemovePhoto;
        }
        btn.addEventListener('click', function(){
            box.checked = !box.checked;
            applyState();
        });
    });

    function updatePhotoList(input, targetId){
        var el = document.getElementById(targetId);
        if(!el) return;
        var n = input.files.length;
        if(n === 0){ el.style.display = 'none'; return; }
        var names = [];
        for(var i = 0; i < n; i++) names.push(input.files[i].name);
        el.textContent = n + ' ' + (n > 1 ? ehPhotosLabel : ehPhotoLabel) + ' ' + ehSelectedLabel + names.join(', ');
        el.style.display = 'block';
    }

    var kebeleInput = document.querySelector('input[name="kebele"]');
    var streetInput = document.querySelector('input[name="street"]');
    var houseNumInput = document.querySelector('input[name="house_num"]');
    var mapLinkInput = document.getElementById('map_link');
    var autoGenNote = document.getElementById('auto-gen-note');
    var mapAutoToggle = document.getElementById('map_auto');

    function updateMapLink(){
        if(!mapAutoToggle.checked) return;
        var kebele = (kebeleInput.value || '').trim();
        var street = (streetInput.value || '').trim();
        var houseNum = (houseNumInput.value || '').trim();
        if(kebele || street){
            var parts = [];
            if(kebele) parts.push('Kebele ' + kebele);
            if(street) parts.push(street);
            if(houseNum) parts.push('House ' + houseNum);
            parts.push('Adama, Ethiopia');
            mapLinkInput.value = 'https://www.google.com/maps/search/' + encodeURIComponent(parts.join(', '));
            autoGenNote.style.display = 'block';
        } else {
            mapLinkInput.value = '';
            autoGenNote.style.display = 'none';
        }
    }
    kebeleInput.addEventListener('input', updateMapLink);
    streetInput.addEventListener('input', updateMapLink);
    houseNumInput.addEventListener('input', updateMapLink);
    mapAutoToggle.addEventListener('change', function(){
        if(this.checked){ updateMapLink(); } else { autoGenNote.style.display = 'none'; }
    });
    </script>

    <?php include(__DIR__ . '/includes/popup.php'); ?>

    <?php if($updated): ?>
    <div class="ph-overlay ph-active" id="phSuccess">
        <div class="ph-card">
            <div class="ph-icon"><i class="fas fa-check"></i></div>
            <h2><?php echo t('eh_saved_title'); ?></h2>
            <p><?php echo t('eh_saved_desc'); ?></p>
            <div class="ph-actions">
                <a href="edit_house.php?id=<?php echo (int)$house_id; ?>" class="ph-btn ph-btn-ghost"><i class="fas fa-pen"></i> <?php echo t('eh_keep_editing'); ?></a>
                <a href="manage_houses.php" class="ph-btn ph-btn-primary"><i class="fas fa-th-large"></i> <?php echo t('ph_go_dashboard'); ?></a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>