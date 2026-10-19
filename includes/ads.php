<?php
// ── Advertisement helpers ─────────────────────────────────────────────
// Shared by the admin advertising console (admin_manage_ads.php) and the
// public homepage banner (Home.php). Requires includes/db.php first so the
// `advertisements` table exists (ads_ensure_schema() is called from there).
//
// ── TIMEZONE STRATEGY (documented) ────────────────────────────────────
// * All advertisement start/end values are stored as DATETIME wall-clock
//   values in AD_TIMEZONE (Africa/Addis_Ababa = Eastern Africa Time, UTC+3,
//   no daylight saving — Ethiopia never changes its clocks).
// * The date/time pickers in the admin form are interpreted in AD_TIMEZONE
//   and stored as plain wall-clock text, so what the admin types is exactly
//   what is stored and displayed.
// * Eligibility is decided by the SERVER clock only (PHP), converted to
//   AD_TIMEZONE — never by browser/JavaScript timers:
//       visible  <=>  status = 'published'
//                  AND start_datetime <= now  AND  end_datetime >  now
//   The single value returned by ads_now() is cached per request so every
//   comparison in one page load uses the same instant.
// * MySQL's timezone/`NOW()` is never used for ad scheduling, so the DB
//   server clock and PHP server clock cannot disagree. created_at/updated_at
//   are also written from ads_now() for consistent display.

if(!defined('AD_TIMEZONE'))        define('AD_TIMEZONE', 'Africa/Addis_Ababa');
if(!defined('AD_TZ_LABEL'))        define('AD_TZ_LABEL', 'Africa/Addis_Ababa (EAT, UTC+3)');
if(!defined('AD_MAX_IMAGE_BYTES')) define('AD_MAX_IMAGE_BYTES', 4 * 1024 * 1024); // 4 MB
if(!defined('AD_MAX_VIDEO_BYTES')) define('AD_MAX_VIDEO_BYTES', 20 * 1024 * 1024); // 20 MB (php.ini upload_max_filesize = 40M)
if(!defined('AD_UPLOAD_DIR'))      define('AD_UPLOAD_DIR', __DIR__ . '/../uploads');

// Optional promo video: extension => container signatures accepted for it.
if(!defined('AD_VIDEO_EXTS'))      define('AD_VIDEO_EXTS', 'mp4,webm,ogg,mov');

// Salt for the visitor fingerprint used by the visitor counter. It never
// leaves the server; only the sha256 digest of salt|IP is stored, so the
// raw IP address is not kept anywhere in the database.
if(!defined('AD_TRACK_SALT')){
    $ad_seed = '';
    foreach(['GOOGLE_CLIENT_SECRET', 'BREVO_API_KEY', 'DB_PASS', 'APP_BASE_URL'] as $ad_sk){
        if(defined($ad_sk) && is_string(constant($ad_sk)) && constant($ad_sk) !== ''){
            $ad_seed = constant($ad_sk);
            break;
        }
    }
    define('AD_TRACK_SALT', hash('sha256', 'ethiorent-ad-track|' . $ad_seed . '|' . (defined('DB_HOST') ? DB_HOST : 'local')));
    unset($ad_seed, $ad_sk);
}

// ── Time helpers ──────────────────────────────────────────────────────

function ads_timezone(){
    static $tz = null;
    if($tz === null){
        try { $tz = new DateTimeZone(AD_TIMEZONE); }
        catch (Exception $e) { $tz = new DateTimeZone('UTC'); }
    }
    return $tz;
}

// Current server time, formatted for AD_TIMEZONE. Cached for the request.
function ads_now(){
    static $now = null;
    if($now === null){
        $now = (new DateTimeImmutable('now', ads_timezone()))->format('Y-m-d H:i:s');
    }
    return $now;
}

// "2026-10-17T14:30" (datetime-local) or "2026-10-17 14:30[:ss]" -> 'Y-m-d H:i:s'
// Returns false when the value is missing or not a real calendar date/time.
function ads_parse_datetime($value){
    $value = trim((string)$value);
    if($value === '') return false;

    $formats = ['Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
    foreach($formats as $fmt){
        $dt = DateTimeImmutable::createFromFormat($fmt, $value, ads_timezone());
        if($dt === false) continue;
        $errors = DateTimeImmutable::getLastErrors();
        if(is_array($errors) && (!empty($errors['warning_count']) || !empty($errors['error_count']))) continue;
        return $dt->format('Y-m-d H:i:s');
    }
    return false;
}

// Human friendly display, e.g. "Oct 17, 2026 · 14:30" (24h, unambiguous).
function ads_format_datetime($value){
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$value, ads_timezone());
    if($dt === false) return (string)$value;
    return $dt->format('M j, Y · H:i');
}

// Value for <input type="datetime-local">, e.g. "2026-10-17T14:30".
function ads_input_datetime($value){
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$value, ads_timezone());
    if($dt === false) return '';
    return $dt->format('Y-m-d\TH:i');
}

// ── Status helpers ────────────────────────────────────────────────────
// Stored status  : 'draft' | 'published' | 'inactive'
// Displayed status: Draft | Scheduled | Active | Expired | Inactive
// (Scheduled/Active/Expired are derived from the server clock so ads turn
//  on and off automatically without any cron job or browser timer.)

function ads_status_key($status, $start, $end, $now = null){
    $status = strtolower(trim((string)$status));
    if($status === 'draft') return 'draft';
    if($status !== 'published') return 'inactive'; // deactivated / unknown fail-safe
    if($now === null) $now = ads_now();
    if((string)$start !== '' && (string)$now < (string)$start) return 'scheduled';
    if((string)$end !== '' && (string)$now >= (string)$end) return 'expired';
    return 'active';
}

function ads_status_meta($key){
    switch($key){
        case 'active':    return ['Active',    'ad-st ad-st-active'];
        case 'scheduled': return ['Scheduled', 'ad-st ad-st-scheduled'];
        case 'expired':   return ['Expired',   'ad-st ad-st-expired'];
        case 'inactive':  return ['Inactive',  'ad-st ad-st-inactive'];
        default:          return ['Draft',     'ad-st ad-st-draft'];
    }
}

// ── Target URL validation ─────────────────────────────────────────────
// Only http/https absolute URLs are allowed — javascript:, data:, vbscript:,
// file: etc. are rejected, and whitespace/control characters are stripped
// out by rejection so a crafted value can never break out of the href.

function ads_safe_url($url){
    $url = trim((string)$url);
    if($url === '') return '';
    if(strlen($url) > 500) return '';
    if(preg_match('/[\x00-\x1F\x7F\s]/', $url)) return '';
    if(!preg_match('#^https?://#i', $url)) return '';
    if(!filter_var($url, FILTER_VALIDATE_URL)) return '';
    return $url;
}

// ── Image upload ──────────────────────────────────────────────────────
// Whitelisted extensions + real MIME sniffing (fileinfo) + getimagesize(),
// so an executable renamed to .jpg is rejected. Filenames are random
// (ad_<time>_<hex>.<ext>), the original name is never used, and only
// image extensions are accepted (no .php/.svg/.html).

function ads_upload_image($file, &$error){
    $error = '';
    if(!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])){
        $error = 'Please choose a banner image to upload.';
        return '';
    }
    if($file['error'] !== UPLOAD_ERR_OK){
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'The image is larger than the server allows.',
            UPLOAD_ERR_FORM_SIZE  => 'The image is larger than the form allows.',
            UPLOAD_ERR_PARTIAL    => 'The image was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temp folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server configuration error: could not write the file.',
            UPLOAD_ERR_EXTENSION  => 'A server extension stopped the upload.',
        ];
        $error = $map[$file['error']] ?? 'Unknown upload error (code ' . (int)$file['error'] . ').';
        return '';
    }
    if((int)$file['size'] <= 0){
        $error = 'The uploaded image is empty.';
        return '';
    }
    if((int)$file['size'] > AD_MAX_IMAGE_BYTES){
        $error = 'Image must be 4 MB or smaller.';
        return '';
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if(!in_array($ext, $allowed_ext, true)){
        $error = 'Only JPG, JPEG, PNG, WebP or GIF images are allowed.';
        return '';
    }

    $tmp = $file['tmp_name'];
    if(!is_uploaded_file($tmp)){
        $error = 'Invalid upload.';
        return '';
    }

    $mime = '';
    if(function_exists('finfo_open')){
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if($finfo){
            $mime = (string)@finfo_file($finfo, $tmp);
            finfo_close($finfo);
        }
    }
    $info = @getimagesize($tmp);
    $real_mime = is_array($info) ? (string)($info['mime'] ?? '') : '';

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if($info === false || !in_array($real_mime, $allowed_mimes, true) || ($mime !== '' && !in_array($mime, $allowed_mimes, true))){
        $error = 'The uploaded file is not a valid image.';
        return '';
    }

    $mime_ext = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
        'image/gif'  => ['gif'],
    ];
    if(!in_array($ext, $mime_ext[$real_mime], true)){
        $error = 'The file extension does not match the actual image type.';
        return '';
    }

    if(!is_dir(AD_UPLOAD_DIR)) @mkdir(AD_UPLOAD_DIR, 0755, true);
    if(!is_writable(AD_UPLOAD_DIR)){
        $error = 'The uploads folder is not writable.';
        return '';
    }

    $fname = 'ad_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = AD_UPLOAD_DIR . '/' . $fname;
    if(!move_uploaded_file($tmp, $target)){
        $error = 'Could not save the uploaded image.';
        return '';
    }
    @chmod($target, 0644);
    return $fname;
}

// Delete an ad image that lives inside uploads/ (basename + realpath guard,
// so a crafted image_path can never escape the upload directory).
function ads_delete_image($filename){
    $filename = trim((string)$filename);
    if($filename === '') return;
    $dir  = realpath(AD_UPLOAD_DIR);
    $file = realpath(AD_UPLOAD_DIR . '/' . basename($filename));
    if($dir && $file && is_file($file) && strpos($file, $dir . DIRECTORY_SEPARATOR) === 0){
        @unlink($file);
    }
}

// Same guarded delete, expressed for the video file (same uploads/ folder).
function ads_delete_video($filename){
    ads_delete_image($filename);
}

// ── Optional promo video upload ───────────────────────────────────────
// Videos are validated by CONTAINER SIGNATURE rather than extension alone:
//   mp4/mov  → bytes 4..8 must be "ftyp"
//   webm     → EBML magic 1A 45 DF A3
//   ogg/ogv  → "OggS"
// A renamed .php/.html file fails the signature test, and the whole payload
// is additionally scanned for PHP/HTML script markers before it is stored.

function ads_video_signature_ok($tmp, $ext){
    $head = @file_get_contents($tmp, false, null, 0, 16);
    if($head === false || $head === '') return false;
    if($ext === 'ogg')  return substr($head, 0, 4) === 'OggS';
    if($ext === 'webm') return substr($head, 0, 4) === "\x1A\x45\xDF\xA3";
    if($ext === 'mp4' || $ext === 'mov') return substr($head, 4, 4) === 'ftyp';
    return false;
}

// Streams the file in 64 KB chunks looking for script markers.
function ads_contains_script($path){
    $fp = @fopen($path, 'rb');
    if(!$fp) return false;
    $needle = ["<?php", "<script"];
    $tail = '';
    while(!feof($fp)){
        $chunk = fread($fp, 65536);
        if($chunk === false) break;
        $haystack = $tail . $chunk;
        foreach($needle as $n){
            if(stripos($haystack, $n) !== false){ fclose($fp); return true; }
        }
        $tail = substr($chunk, -8); // keep overlap for markers split across chunks
    }
    fclose($fp);
    return false;
}

function ads_upload_video($file, &$error){
    $error = '';
    if(!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])){
        $error = 'Please choose a video file to upload.';
        return '';
    }
    if($file['error'] !== UPLOAD_ERR_OK){
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'The video is larger than the server allows (check upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'The video is larger than the form allows.',
            UPLOAD_ERR_PARTIAL    => 'The video was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temp folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server configuration error: could not write the file.',
            UPLOAD_ERR_EXTENSION  => 'A server extension stopped the upload.',
        ];
        $error = $map[$file['error']] ?? 'Unknown upload error (code ' . (int)$file['error'] . ').';
        return '';
    }
    if((int)$file['size'] <= 0){
        $error = 'The uploaded video is empty.';
        return '';
    }
    if((int)$file['size'] > AD_MAX_VIDEO_BYTES){
        $error = 'Video must be 20 MB or smaller.';
        return '';
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allowed = ['mp4', 'webm', 'ogg', 'ogv', 'mov'];
    if(!in_array($ext, $allowed, true)){
        $error = 'Only ' . AD_VIDEO_EXTS . ' video files are allowed.';
        return '';
    }
    if($ext === 'ogv') $ext = 'ogg'; // normalise the container extension

    $tmp = $file['tmp_name'];
    if(!is_uploaded_file($tmp)){
        $error = 'Invalid upload.';
        return '';
    }

    if(!ads_video_signature_ok($tmp, $ext)){
        $error = 'The uploaded file is not a valid ' . strtoupper($ext) . ' video.';
        return '';
    }
    if(ads_contains_script($tmp)){
        $error = 'The video file contains script code and was rejected.';
        return '';
    }
    // Extra safety net when fileinfo is available: reject anything the
    // MIME sniffer already recognises as a script/HTML document.
    if(function_exists('finfo_open')){
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if($finfo){
            $mime = (string)@finfo_file($finfo, $tmp);
            finfo_close($finfo);
            $deny = ['text/html', 'application/xhtml+xml', 'text/x-php', 'application/x-php',
                     'application/x-httpd-php', 'application/x-httpd-php-source', 'application/php',
                     'text/javascript', 'application/javascript'];
            if(in_array($mime, $deny, true)){
                $error = 'The uploaded file is not a valid video.';
                return '';
            }
        }
    }

    if(!is_dir(AD_UPLOAD_DIR)) @mkdir(AD_UPLOAD_DIR, 0755, true);
    if(!is_writable(AD_UPLOAD_DIR)){
        $error = 'The uploads folder is not writable.';
        return '';
    }

    $fname = 'advid_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = AD_UPLOAD_DIR . '/' . $fname;
    if(!move_uploaded_file($tmp, $target)){
        $error = 'Could not save the uploaded video.';
        return '';
    }
    @chmod($target, 0644);
    return $fname;
}

// ── Visitor counter ───────────────────────────────────────────────────
// Every advertisement card on the homepage reports a "view" (seen) event and
// a "click" event to ad_track.php. Counting rules:
//   * only advertisements that are ACTIVE RIGHT NOW are counted;
//   * views are deduplicated per visitor per calendar day (a refresh does not
//     inflate the number), clicks are counted individually;
//   * the stored key is sha256(AD_TRACK_SALT|ip) — the raw IP is never saved;
//   * events are recorded server-side from the server clock (AD_TIMEZONE).

function ads_today_start(){
    $d = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', ads_now(), ads_timezone());
    return ($d !== false ? $d->setTime(0, 0, 0) : new DateTimeImmutable('now', ads_timezone()))->format('Y-m-d H:i:s');
}

function ads_visitor_hash($ip = null){
    if($ip === null) $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return hash('sha256', AD_TRACK_SALT . '|' . $ip);
}

// Is this advertisement visible on the homepage at this very moment?
function ads_is_active($conn, $ad_id){
    $ad_id = (int)$ad_id;
    if($ad_id <= 0) return false;
    $now = ads_now();
    $stmt = mysqli_prepare($conn,
        "SELECT 1 FROM advertisements
          WHERE id = ? AND status = 'published' AND start_datetime <= ? AND end_datetime > ?");
    if(!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'iss', $ad_id, $now, $now);
    if(!mysqli_stmt_execute($stmt)) return false;
    $res = mysqli_stmt_get_result($stmt);
    return ($res && mysqli_num_rows($res) > 0);
}

// Record one event. Returns true when the event was stored.
function ads_record_event($conn, $ad_id, $type){
    $ad_id = (int)$ad_id;
    if($ad_id <= 0) return false;
    $type = ($type === 'click') ? 'click' : 'view';
    $now  = ads_now();
    $hash = ads_visitor_hash();

    if($type === 'view'){ // dedupe: one view per visitor per day per advertisement
        $today = ads_today_start();
        $chk = mysqli_prepare($conn,
            "SELECT 1 FROM advertisement_views
              WHERE ad_id = ? AND event_type = 'view' AND visitor_hash = ? AND viewed_at >= ? LIMIT 1");
        if(!$chk) return false;
        mysqli_stmt_bind_param($chk, 'iss', $ad_id, $hash, $today);
        if(!mysqli_stmt_execute($chk)) return false;
        $dup = mysqli_stmt_get_result($chk);
        if($dup && mysqli_num_rows($dup) > 0) return false; // already counted today
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO advertisement_views (ad_id, event_type, visitor_hash, viewed_at)
         VALUES (?, ?, ?, ?)");
    if(!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'isss', $ad_id, $type, $hash, $now);
    return mysqli_stmt_execute($stmt);
}

// Per-advertisement counters for the admin console, in one grouped query.
//   visitors = distinct visitors, visitors_today = distinct visitors today,
//   views    = view events (deduped per visitor/day), clicks = CTA clicks.
function ads_ad_metrics($conn){
    $out = [];
    $today = ads_today_start();
    $stmt = mysqli_prepare($conn,
        "SELECT ad_id,
                COUNT(DISTINCT CASE WHEN event_type = 'view' THEN visitor_hash END)                AS visitors,
                COUNT(DISTINCT CASE WHEN event_type = 'view' AND viewed_at >= ? THEN visitor_hash END) AS visitors_today,
                SUM(CASE WHEN event_type = 'view'  THEN 1 ELSE 0 END)                              AS views,
                SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END)                              AS clicks
           FROM advertisement_views
          GROUP BY ad_id");
    if(!$stmt) return $out;
    mysqli_stmt_bind_param($stmt, 's', $today);
    if(!mysqli_stmt_execute($stmt)) return $out;
    $res = mysqli_stmt_get_result($stmt);
    if(!$res) return $out;
    while($r = mysqli_fetch_assoc($res)){
        $out[(int)$r['ad_id']] = [
            'visitors'      => (int)$r['visitors'],
            'visitors_today'=> (int)$r['visitors_today'],
            'views'         => (int)$r['views'],
            'clicks'        => (int)$r['clicks'],
        ];
    }
    return $out;
}

// ── Homepage query ────────────────────────────────────────────────────
// Only published ads whose schedule covers the current server time are
// returned, so scheduled ads appear by themselves when their start time is
// reached and disappear by themselves once their end time passes.

function ads_active_ads($conn, $limit = 6){
    $limit = max(1, min(12, (int)$limit));
    $now = ads_now();
    $rows = [];
    $stmt = mysqli_prepare($conn,
        "SELECT id, title, description, image_path, video_path, target_url, start_datetime, end_datetime
           FROM advertisements
          WHERE status = 'published' AND start_datetime <= ? AND end_datetime > ?
          ORDER BY start_datetime ASC, id ASC
          LIMIT " . $limit);
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $now, $now);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if($res) $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
    return $rows;
}

// ── Homepage rendering ────────────────────────────────────────────────
// Prints the whole advertisement section (styles included). Prints NOTHING
// when no ad is active, so no empty space is left behind.

function ads_render_home($conn){
    $ads = ads_active_ads($conn);
    if(!$ads) return;

    $label   = function_exists('t') ? t('ads_label')     : 'Advertisement';
    $more    = function_exists('t') ? t('ads_learn_more') : 'Learn more';
    $vidLbl  = function_exists('t') ? t('ads_video')     : 'Video';
?>
<style>
    .ads-section{background:#fff;padding:56px 24px 8px;border-bottom:1px solid #f1f5f9}
    .ads-section .section-header{margin-bottom:32px}
    .ads-grid{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
    .ad-card{display:flex;flex-direction:column;background:#fff;border:1px solid #e9eff5;border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .3s cubic-bezier(.2,.7,.3,1),box-shadow .3s,border-color .3s}
    .ad-card:hover{transform:translateY(-6px);box-shadow:0 22px 46px -20px rgba(15,23,42,.28);border-color:#dbe5ee}
    .ad-media{position:relative;height:170px;background:linear-gradient(135deg,#0f172a 0%,#078930 100%);overflow:hidden}
    .ad-media img{width:100%;height:100%;object-fit:cover;transition:transform .55s cubic-bezier(.2,.7,.3,1)}
    .ad-card:hover .ad-media img{transform:scale(1.05)}
    .ad-media video{width:100%;height:100%;object-fit:cover;display:block;background:#0f172a;cursor:pointer}
    .ad-media .ad-flag{position:absolute;top:12px;left:12px;background:rgba(15,23,42,.78);color:#fff;padding:5px 11px;border-radius:50px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;backdrop-filter:blur(8px);pointer-events:none}
    .ad-media .ad-flag i{color:#FCDD09;font-size:9px;margin-right:5px}
    .ad-media .ad-flag-video{left:auto;right:12px;background:rgba(7,137,48,.94)}
    .ad-media .ad-flag-video i{color:#fff;font-size:10px}
    .ad-body{padding:16px 18px 18px;display:flex;flex-direction:column;flex:1}
    .ad-body h3{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:6px;letter-spacing:-.2px}
    .ad-body p{font-size:13.5px;color:#64748b;line-height:1.6;margin-bottom:12px;display:-webkit-box;line-clamp:3;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .ad-cta{margin-top:auto;display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:#078930;text-decoration:none;align-self:flex-start}
    .ad-cta i{font-size:11px;transition:transform .25s}
    .ad-card:hover .ad-cta i{transform:translateX(3px)}
    .ad-card.is-video{cursor:default}
    .ad-card.is-video .ad-media{background:#000}
    @media(max-width:768px){.ads-section{padding:40px 16px 4px}.ads-grid{grid-template-columns:1fr;gap:16px}.ad-media{height:150px}}
</style>
<section class="ads-section" aria-label="<?php echo htmlspecialchars($label, ENT_QUOTES); ?>">
    <div class="section-header" style="margin-bottom:28px">
        <span class="tag"><?php echo htmlspecialchars($label, ENT_QUOTES); ?></span>
    </div>
    <div class="ads-grid">
        <?php foreach($ads as $ad):
            $title = (string)($ad['title'] ?? '');
            $desc  = (string)($ad['description'] ?? '');
            $img   = basename((string)($ad['image_path'] ?? ''));
            $vid   = basename((string)($ad['video_path'] ?? ''));
            $url   = ads_safe_url($ad['target_url'] ?? '');
            $hasVid = $vid !== '';
            $inner = '';
            $inner .= '<div class="ad-media">';
            $inner .= '<span class="ad-flag"><i class="fas fa-bullhorn"></i>' . htmlspecialchars($label, ENT_QUOTES) . '</span>';
            if($hasVid){
                $inner .= '<span class="ad-flag ad-flag-video"><i class="fas fa-circle-play"></i>' . htmlspecialchars($vidLbl, ENT_QUOTES) . '</span>';
                $inner .= '<video muted playsinline loop controls preload="none"'
                    . ' src="uploads/' . htmlspecialchars($vid, ENT_QUOTES) . '"'
                    . ($img !== '' ? ' poster="uploads/' . htmlspecialchars($img, ENT_QUOTES) . '"' : '')
                    . ' aria-label="' . htmlspecialchars($title, ENT_QUOTES) . '"></video>';
            } elseif($img !== ''){
                $inner .= '<img src="uploads/' . htmlspecialchars($img, ENT_QUOTES) . '" alt="' . htmlspecialchars($title, ENT_QUOTES) . '" loading="lazy" onerror="this.style.display=\'none\'">';
            }
            $inner .= '</div>';
            $inner .= '<div class="ad-body">';
            $inner .= '<h3>' . htmlspecialchars($title, ENT_QUOTES) . '</h3>';
            if($desc !== ''){
                $inner .= '<p>' . htmlspecialchars($desc, ENT_QUOTES) . '</p>';
            }
            if($url !== ''){
                $ctaText = htmlspecialchars($more, ENT_QUOTES) . ' <i class="fas fa-arrow-right"></i>';
                // With a video the whole card is NOT a link (the <video>
                // controls must stay clickable) — only the call to action is.
                $inner .= $hasVid
                    ? '<a class="ad-cta" href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer sponsored nofollow">' . $ctaText . '</a>'
                    : '<span class="ad-cta">' . $ctaText . '</span>';
            }
            $inner .= '</div>';

            $cardAttrs = 'class="ad-card' . ($hasVid ? ' is-video' : '') . '" data-ad="' . (int)$ad['id'] . '"';
            if($url !== '' && !$hasVid){
                echo '<a ' . $cardAttrs . ' href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer sponsored nofollow">' . $inner . '</a>';
            } else {
                echo '<div ' . $cardAttrs . '>' . $inner . '</div>';
            }
        endforeach; ?>
    </div>
</section>
<script>
/* Advertisement analytics + the optional in-card video player.
   Nothing here is required for the ads to display — if JS is off the cards
   render normally and the videos simply wait for the visitor to press play. */
(function(){
    var section = document.querySelector('.ads-section');
    if(!section) return;
    var cards = Array.prototype.slice.call(section.querySelectorAll('.ad-card[data-ad]'));
    if(!cards.length) return;

    function send(id, type){
        try{
            var body = 'ad=' + encodeURIComponent(id) + '&t=' + encodeURIComponent(type);
            if(navigator.sendBeacon){
                navigator.sendBeacon('ad_track.php',
                    new Blob([body], {type:'application/x-www-form-urlencoded'}));
                return;
            }
            fetch('ad_track.php', {
                method:'POST',
                headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'},
                body:body,
                keepalive:true
            })['catch'](function(){});
        }catch(e){}
    }
    function countOnce(el, type){
        var key = '__ad' + type;
        if(el[key]) return;
        el[key] = 1;
        send(el.getAttribute('data-ad'), type);
    }

    // Clicks: fires when the card or its call-to-action link is used.
    cards.forEach(function(card){
        var link = card.tagName === 'A' ? card : card.querySelector('a.ad-cta');
        if(link){
            link.addEventListener('click', function(){ countOnce(card, 'click'); });
        }
    });

    function playVideo(card){
        var v = card.querySelector('video');
        if(!v) return;
        if(v.preload === 'none'){ v.preload = 'metadata'; v.load(); }
        var p = v.play();
        if(p && p['catch']) p['catch'](function(){});
    }

    if('IntersectionObserver' in window){
        // A card counts as a visitor only after it stays at least half
        // visible for 1 full second (no drive-by impressions).
        var observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                var card = entry.target;
                if(entry.isIntersecting){
                    if(card.__adTimer) return;
                    card.__adTimer = setTimeout(function(){
                        card.__adTimer = 0;
                        countOnce(card, 'view');
                        playVideo(card);
                    }, 1000);
                } else if(card.__adTimer){
                    clearTimeout(card.__adTimer);
                    card.__adTimer = 0;
                }
            });
        }, {threshold:0.5});
        cards.forEach(function(card){ observer.observe(card); });
    } else {
        // Old browser: count every card after 2 seconds and start one video.
        setTimeout(function(){
            cards.forEach(function(card){ countOnce(card, 'view'); });
            if(cards[0]) playVideo(cards[0]);
        }, 2000);
    }
})();
</script>
<?php
}

// ── Schema (idempotent, called from includes/db.php) ──────────────────

function ads_ensure_schema($conn){
    static $done = false;
    if($done) return;
    $done = true;

    $create = "CREATE TABLE IF NOT EXISTS advertisements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        image_path VARCHAR(255) NOT NULL,
        video_path VARCHAR(255) NULL,
        target_url VARCHAR(500) NULL,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'draft',
        created_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ad_status_window (status, start_datetime, end_datetime),
        KEY idx_ad_created_by (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $create_views = "CREATE TABLE IF NOT EXISTS advertisement_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        event_type VARCHAR(16) NOT NULL DEFAULT 'view',
        visitor_hash CHAR(64) NOT NULL,
        viewed_at DATETIME NOT NULL,
        INDEX idx_adv_views_ad (ad_id, viewed_at),
        INDEX idx_adv_views_hash (visitor_hash),
        INDEX idx_adv_views_kind (event_type, viewed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    // Kept in try/catch so an exotic shared-hosting engine/collation
    // mismatch can never break the whole application on page load.
    try {
        mysqli_query($conn, $create);
        mysqli_query($conn, $create_views);

        // Additive migrations for installs created before these features.
        $col = @mysqli_query($conn, "SHOW COLUMNS FROM advertisements LIKE 'video_path'");
        if(!$col || mysqli_num_rows($col) === 0){
            @mysqli_query($conn, "ALTER TABLE advertisements ADD COLUMN video_path VARCHAR(255) NULL AFTER image_path");
        }

        $fk = @mysqli_query($conn,
            "SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'advertisements'
                AND CONSTRAINT_NAME = 'fk_adverts_created_by'");
        $fk_row = $fk ? mysqli_fetch_assoc($fk) : null;
        if(!$fk_row || (int)$fk_row['c'] === 0){
            mysqli_query($conn,
                "ALTER TABLE advertisements
                    ADD CONSTRAINT fk_adverts_created_by
                    FOREIGN KEY (created_by) REFERENCES users(id)
                    ON DELETE SET NULL ON UPDATE CASCADE");
        }

        // Deleting an advertisement removes its visitor history with it.
        $fk2 = @mysqli_query($conn,
            "SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'advertisement_views'
                AND CONSTRAINT_NAME = 'fk_adv_views_ad'");
        $fk2_row = $fk2 ? mysqli_fetch_assoc($fk2) : null;
        if(!$fk2_row || (int)$fk2_row['c'] === 0){
            mysqli_query($conn,
                "ALTER TABLE advertisement_views
                    ADD CONSTRAINT fk_adv_views_ad
                    FOREIGN KEY (ad_id) REFERENCES advertisements(id)
                    ON DELETE CASCADE ON UPDATE CASCADE");
        }
    } catch (Throwable $e) {
        // Schema problems surface later as query errors instead of fataling here.
    }
}
