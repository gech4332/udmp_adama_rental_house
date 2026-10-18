<?php
// ── Advertisement analytics beacon ────────────────────────────────────
// Counts VISITORS (views) and CLICKS for advertisements. Fired by the small
// script at the bottom of the advertisement section on Home.php.
//
// Security / integrity rules:
//   * POST only — a GET can never change data (no side effects on crawl).
//   * Same-origin only (Origin/Referer host, X-Requested-With or Sec-Fetch-Site).
//   * The advertisement must be ACTIVE RIGHT NOW — scheduled, expired,
//     draft, deactivated, deleted or unknown ids are simply ignored.
//   * Only sha256(salt|IP) is stored; the raw IP is never written anywhere.
//   * Views are deduplicated per visitor per day in the database, so a
//     refresh (or a replay of the same beacon) does not inflate the count.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

function ad_track_same_origin(){
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if($host === '') return false;
    foreach(['HTTP_ORIGIN', 'HTTP_REFERER'] as $h){
        if(!empty($_SERVER[$h])){
            $p = @parse_url((string)$_SERVER[$h]);
            if(is_array($p) && isset($p['host']) && strtolower((string)$p['host']) === $host) return true;
        }
    }
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'){
        return true;
    }
    $site = isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? strtolower((string)$_SERVER['HTTP_SEC_FETCH_SITE']) : '';
    return ($site === 'same-origin' || $site === 'same-site');
}

if(!ad_track_same_origin()){
    http_response_code(403);
    echo json_encode(['ok' => false, 'why' => 'cross-origin']);
    exit;
}

// includes/db.php also loads includes/ads.php and applies the schema.
require_once __DIR__ . '/includes/db.php';

$ad_id = (int)($_POST['ad'] ?? 0);
$type  = (isset($_POST['t']) && $_POST['t'] === 'click') ? 'click' : 'view';

if($ad_id <= 0 || !ads_is_active($conn, $ad_id)){
    echo json_encode(['ok' => false]);
    exit;
}

$stored = ads_record_event($conn, $ad_id, $type);
echo json_encode(['ok' => true, 'stored' => (bool)$stored, 'type' => $type]);
