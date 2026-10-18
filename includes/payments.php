<?php
// ─────────────────────────────────────────────────────────────────────
// Owner service-fee payments + secure rental-request acceptance.
//
// An owner must pay a service fee (2% of the first month's rent) before
// they may accept a tenant's rental request. Only after the fee is
// VERIFIED and the owner has accepted does the requesting tenant get to
// see the owner's name, phone, exact map coordinates and house number.
//
// Design:
//   * Provider-agnostic layer. Payments are recorded in `rental_payments`
//     with a rent/fee snapshot so the amount can never be tampered with
//     from the browser.
//   * Adapters: `chapa` (real/sandbox HTTP API), `test` (local simulated
//     sandbox so the flow is testable without real transactions) and
//     `manual` (an admin marks a payment verified — kept visibly separate
//     from gateway-verified payments via verification_source).
//   * Callbacks/webhooks are idempotent; success is only ever recorded
//     after a server-side verification, never from a browser parameter.
//
// Schema is created/updated idempotently by payments_ensure_schema(),
// called from includes/db.php on every boot.
// ─────────────────────────────────────────────────────────────────────

// ── Fee / currency ───────────────────────────────────────────────────

function payment_fee_rate(){
    if(defined('PAYMENT_FEE_RATE')){
        $r = (float)PAYMENT_FEE_RATE;
        if($r > 0 && $r < 1) return $r;
    }
    return 0.02; // 2% of the first month's rent
}

function payment_currency(){
    $c = defined('PAYMENT_CURRENCY') ? trim((string)PAYMENT_CURRENCY) : '';
    return $c !== '' ? strtoupper($c) : 'ETB';
}

// Fee is always derived server-side from a stored rent snapshot.
function payment_calc_fee($rent){
    return round(((float)$rent) * payment_fee_rate(), 2);
}

function payment_format_amount($amount){
    return number_format((float)$amount, 2, '.', '');
}

function payment_money_label($amount){
    return number_format((float)$amount, 2) . ' ' . payment_currency();
}

// ── Provider resolution ──────────────────────────────────────────────

function payment_chapa_configured(){
    return defined('CHAPA_SECRET_KEY') && trim((string)CHAPA_SECRET_KEY) !== '';
}

// Public Chapa business identifier (not a secret) — reference/logging only.
function payment_chapa_merchant_id(){
    return defined('CHAPA_MERCHANT_ID') ? trim((string)CHAPA_MERCHANT_ID) : '';
}

// Returns one of: chapa | test | manual.
function payment_provider(){
    $allowed = ['chapa', 'test', 'manual'];
    if(defined('PAYMENT_GATEWAY')){
        $g = strtolower(trim((string)PAYMENT_GATEWAY));
        if(in_array($g, $allowed, true)){
            if($g === 'chapa' && !payment_chapa_configured()) return 'test';
            return $g;
        }
    }
    return payment_chapa_configured() ? 'chapa' : 'test';
}

function payment_provider_label($p){
    switch($p){
        case 'chapa': return 'Chapa';
        case 'manual': return 'Manual (admin verified)';
        case 'test': return 'Test mode (sandbox)';
        default: return ucfirst((string)$p);
    }
}

// Test mode means no real money moves; used when credentials are absent.
function payment_is_test_mode(){
    return payment_provider() === 'test';
}

function payment_base_url(){
    if(defined('APP_BASE_URL') && trim((string)APP_BASE_URL) !== ''){
        return rtrim(APP_BASE_URL, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

// ── Schema (idempotent, called from includes/db.php) ─────────────────

function payments_ensure_schema($conn){
    static $done = false;
    if($done) return;
    $done = true;

    $create = "CREATE TABLE IF NOT EXISTS rental_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rental_request_id INT NOT NULL,
        house_id INT NOT NULL,
        owner_id INT NOT NULL,
        tenant_id INT NOT NULL,
        rent_snapshot INT NOT NULL,
        fee_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0200,
        fee_amount DECIMAL(12,2) NOT NULL,
        currency VARCHAR(8) NOT NULL DEFAULT 'ETB',
        provider VARCHAR(30) NOT NULL DEFAULT 'test',
        reference VARCHAR(64) NOT NULL,
        gateway_tx_id VARCHAR(128) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        verification_source VARCHAR(20) NULL,
        pay_url VARCHAR(500) NULL,
        verified_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_payment_reference (reference),
        KEY idx_rp_request (rental_request_id),
        KEY idx_rp_owner (owner_id),
        KEY idx_rp_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    try {
        mysqli_query($conn, $create);

        // Acceptance timestamp on the request (additive).
        $col = @mysqli_query($conn, "SHOW COLUMNS FROM rental_requests LIKE 'accepted_at'");
        if(!$col || mysqli_num_rows($col) === 0){
            @mysqli_query($conn, "ALTER TABLE rental_requests ADD COLUMN accepted_at DATETIME NULL");
        }

        payments_add_fk($conn, 'fk_rp_request', 'rental_request_id', 'rental_requests', 'CASCADE');
        payments_add_fk($conn, 'fk_rp_house',   'house_id',           'houses',           'CASCADE');
        payments_add_fk($conn, 'fk_rp_owner',   'owner_id',           'users',            'CASCADE');
        payments_add_fk($conn, 'fk_rp_tenant',  'tenant_id',          'users',            'CASCADE');
    } catch (\Throwable $e) {
        // Never let a hosting engine/collation quirk break page loads.
    }
}

function payments_add_fk($conn, $name, $column, $ref_table, $on_delete){
    $sql = "SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'rental_payments'
              AND CONSTRAINT_NAME = ?";
    $stmt = @mysqli_prepare($conn, $sql);
    if(!$stmt) return;
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if($row && (int)$row['c'] > 0) return;

    @mysqli_query($conn,
        "ALTER TABLE rental_payments
            ADD CONSTRAINT `$name`
            FOREIGN KEY (`$column`) REFERENCES `$ref_table`(id)
            ON DELETE $on_delete ON UPDATE CASCADE");
}

// ── Lookups ──────────────────────────────────────────────────────────

function payment_find_by_reference($conn, $reference){
    $stmt = mysqli_prepare($conn, "SELECT * FROM rental_payments WHERE reference=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $reference);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return $res ? mysqli_fetch_assoc($res) : null;
}

function payment_find_by_id($conn, $id){
    $id = (int)$id;
    $stmt = mysqli_prepare($conn, "SELECT * FROM rental_payments WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return $res ? mysqli_fetch_assoc($res) : null;
}

function payment_latest_for_request($conn, $request_id){
    $request_id = (int)$request_id;
    $stmt = mysqli_prepare($conn, "SELECT * FROM rental_payments WHERE rental_request_id=? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return $res ? mysqli_fetch_assoc($res) : null;
}

function payment_verified_for_request($conn, $request_id){
    $request_id = (int)$request_id;
    $stmt = mysqli_prepare($conn, "SELECT * FROM rental_payments WHERE rental_request_id=? AND status='verified' ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return $res ? mysqli_fetch_assoc($res) : null;
}

// True only when a verified fee payment exists for that exact request.
function payment_verified_unlocks($conn, $request_id){
    return payment_verified_for_request($conn, $request_id) !== null;
}

function payment_generate_reference(){
    return 'RP' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
}

// ── Create / update ──────────────────────────────────────────────────

// Returns [row, state] where state is created|reused|not_found.
// Reuses an existing pending/verified payment so a double-click or retry
// never creates a second charge for the same request.
function payment_get_or_create($conn, $request_id){
    $request_id = (int)$request_id;
    $stmt = mysqli_prepare($conn, "
        SELECT rr.id, rr.status AS req_status, rr.user_id AS tenant_id,
               rr.house_id, h.user_id AS owner_id, h.amount
        FROM rental_requests rr
        JOIN houses h ON rr.house_id = h.id
        WHERE rr.id=?");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $req = $res ? mysqli_fetch_assoc($res) : null;
    if(!$req) return [null, 'not_found'];

    $existing = payment_latest_for_request($conn, $request_id);
    if($existing && in_array($existing['status'], ['pending', 'verified'], true)){
        return [$existing, 'reused'];
    }

    $rent = (int)$req['amount'];
    $rate = payment_fee_rate();
    $fee  = payment_calc_fee($rent);
    $cur  = payment_currency();
    $prov = payment_provider();
    $hid  = (int)$req['house_id'];
    $oid  = (int)$req['owner_id'];
    $tid  = (int)$req['tenant_id'];

    for($attempt = 0; $attempt < 5; $attempt++){
        $ref = payment_generate_reference();
        $ins = mysqli_prepare($conn, "
            INSERT INTO rental_payments
                (rental_request_id, house_id, owner_id, tenant_id, rent_snapshot,
                 fee_rate, fee_amount, currency, provider, reference, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
        mysqli_stmt_bind_param($ins, "iiiiiddsss",
            $request_id, $hid, $oid, $tid,
            $rent, $rate, $fee, $cur, $prov, $ref);
        if(mysqli_stmt_execute($ins)){
            return [payment_find_by_id($conn, mysqli_insert_id($conn)), 'created'];
        }
        // Duplicate reference (extremely unlikely) — retry with a new one.
    }
    return [null, 'error'];
}

function payment_set_pay_url($conn, $id, $url, $provider){
    $id = (int)$id;
    $stmt = mysqli_prepare($conn, "UPDATE rental_payments SET pay_url=?, provider=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssi", $url, $provider, $id);
    return mysqli_stmt_execute($stmt);
}

// Idempotent verification. Safe to call repeatedly (webhook + redirect).
function payment_mark_verified($conn, $reference, $gateway_tx_id, $source){
    $row = payment_find_by_reference($conn, $reference);
    if(!$row) return false;
    if($row['status'] === 'verified') return true; // already done

    $stmt = mysqli_prepare($conn, "
        UPDATE rental_payments
           SET status='verified', gateway_tx_id=?, verification_source=?, verified_at=NOW()
         WHERE reference=? AND status<>'verified'");
    mysqli_stmt_bind_param($stmt, "sss", $gateway_tx_id, $source, $reference);
    mysqli_stmt_execute($stmt);
    return true;
}

function payment_mark_failed($conn, $reference, $status){
    if(!in_array($status, ['failed', 'cancelled', 'expired'], true)) $status = 'failed';
    $stmt = mysqli_prepare($conn, "UPDATE rental_payments SET status=? WHERE reference=? AND status<>'verified'");
    mysqli_stmt_bind_param($stmt, "ss", $status, $reference);
    mysqli_stmt_execute($stmt);
}

// ── Gateway adapters ─────────────────────────────────────────────────

// Initiates a checkout for a payment row.
// Returns ['ok'=>bool, 'mode'=>'redirect|manual|error', 'pay_url'=>string, 'message'=>string].
function payment_gateway_initiate($conn, $payment){
    switch($payment['provider']){
        case 'chapa': return payment_chapa_initiate($conn, $payment);
        case 'manual': return ['ok' => true, 'mode' => 'manual', 'pay_url' => '',
            'message' => 'A service fee payment was recorded as pending. An administrator will verify it.'];
        case 'test':
        default:
            return ['ok' => true, 'mode' => 'redirect',
                'pay_url' => 'payment_checkout.php?ref=' . urlencode($payment['reference']),
                'message' => ''];
    }
}

// Server-side verification. Returns ['status'=>'verified|pending|failed', ...].
function payment_gateway_verify($conn, $reference){
    $row = payment_find_by_reference($conn, $reference);
    if(!$row) return ['status' => 'failed', 'message' => 'Unknown reference'];

    if($row['provider'] === 'chapa'){
        return payment_chapa_verify($conn, $row);
    }
    if($row['provider'] === 'manual'){
        // Manual payments are verified by an admin action, not a callback.
        return ['status' => $row['status'] === 'verified' ? 'verified' : 'pending'];
    }
    // test provider: verification only happens through the local sandbox
    // confirmation (payment_callback.php), never here.
    return ['status' => $row['status'] === 'verified' ? 'verified' : 'pending'];
}

function payment_chapa_initiate($conn, $payment){
    $ownerEmail = '';
    $ost = mysqli_prepare($conn, "SELECT email, full_name FROM users WHERE id=? LIMIT 1");
    $owner_id = (int)$payment['owner_id'];
    mysqli_stmt_bind_param($ost, "i", $owner_id);
    mysqli_stmt_execute($ost);
    $ores = mysqli_stmt_get_result($ost);
    $orow = $ores ? mysqli_fetch_assoc($ores) : null;
    if($orow){ $ownerEmail = $orow['email']; }
    if($ownerEmail === '') $ownerEmail = defined('CHAPA_CONTACT_EMAIL') ? CHAPA_CONTACT_EMAIL : 'owner@example.com';

    $name  = trim((string)($orow['full_name'] ?? 'Owner'));
    $parts = preg_split('/\s+/', $name);
    $first = $parts[0] ?? 'Owner';
    $last  = count($parts) > 1 ? $parts[count($parts) - 1] : '';

    $body = [
        'amount'       => payment_format_amount($payment['fee_amount']),
        'currency'     => $payment['currency'],
        'email'        => $ownerEmail,
        'first_name'   => $first,
        'last_name'    => $last,
        'tx_ref'       => $payment['reference'],
        'callback_url' => payment_base_url() . '/payment_webhook.php',
        'return_url'   => payment_base_url() . '/payment_callback.php?ref=' . urlencode($payment['reference']),
        'customization' => [
            'title'       => 'EthioRent owner service fee',
            'description' => '2% service fee — rental request #' . (int)$payment['rental_request_id'],
        ],
    ];

    $resp = payment_http_post_json(
        'https://api.chapa.co/v1/transaction/initialize',
        $body,
        ['Authorization: Bearer ' . CHAPA_SECRET_KEY]
    );

    if(!$resp['ok']){
        return ['ok' => false, 'mode' => 'error', 'message' => 'Could not reach the payment gateway.'];
    }
    $json = json_decode($resp['body'], true);
    if(!is_array($json) || ($json['status'] ?? '') !== 'success' || empty($json['data']['checkout_url'])){
        $msg = is_array($json) ? ($json['message'] ?? 'Gateway rejected the request.') : 'Gateway returned an invalid response.';
        return ['ok' => false, 'mode' => 'error', 'message' => $msg];
    }
    return ['ok' => true, 'mode' => 'redirect', 'pay_url' => $json['data']['checkout_url'], 'message' => ''];
}

function payment_chapa_verify($conn, $payment){
    $resp = payment_http_get(
        'https://api.chapa.co/v1/transaction/verify/' . urlencode($payment['reference']),
        ['Authorization: Bearer ' . CHAPA_SECRET_KEY]
    );
    if(!$resp['ok']) return ['status' => 'pending', 'message' => 'Gateway unreachable'];

    $json = json_decode($resp['body'], true);
    if(!is_array($json)) return ['status' => 'pending', 'message' => 'Invalid gateway response'];

    $data = $json['data'] ?? [];
    $gstatus = strtolower((string)($data['status'] ?? ''));
    $txId = (string)($data['tx_ref'] ?? $payment['reference']);

    if($gstatus === 'success'){
        // Confirm the gateway charged at least the snapshotted fee.
        $paid = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        if($paid + 0.01 < (float)$payment['fee_amount']){
            return ['status' => 'failed', 'message' => 'Amount mismatch'];
        }
        return ['status' => 'verified', 'tx_id' => $txId];
    }
    if(in_array($gstatus, ['failed', 'cancelled', 'reversed'], true)){
        return ['status' => 'failed', 'message' => 'Gateway reported ' . $gstatus];
    }
    return ['status' => 'pending'];
}

function payment_http_post_json($url, array $body, array $headers){
    $ch = curl_init($url);
    $hdrs = array_merge(['Content-Type: application/json'], $headers);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => $hdrs,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if($out === false) return ['ok' => false, 'code' => 0, 'body' => '', 'error' => $err];
    return ['ok' => ($code >= 200 && $code < 300), 'code' => $code, 'body' => $out, 'error' => ''];
}

function payment_http_get($url, array $headers){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if($out === false) return ['ok' => false, 'code' => 0, 'body' => '', 'error' => $err];
    return ['ok' => ($code >= 200 && $code < 300), 'code' => $code, 'body' => $out, 'error' => ''];
}

// ── Reveal authorization ─────────────────────────────────────────────

// Contact details on a listing are revealed to a logged-in user only when
// they are the tenant of an ACCEPTED request for that house AND that exact
// request has a VERIFIED fee payment. Everything is enforced server-side.
function payment_contact_revealed($conn, $house_id, $viewer_id){
    $house_id = (int)$house_id;
    $viewer_id = (int)$viewer_id;
    if($house_id <= 0 || $viewer_id <= 0) return false;

    $stmt = mysqli_prepare($conn, "
        SELECT rr.id
        FROM rental_requests rr
        JOIN rental_payments rp ON rp.rental_request_id = rr.id
        WHERE rr.house_id=? AND rr.user_id=?
          AND rr.status='accepted' AND rp.status='verified'
        ORDER BY rr.id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $house_id, $viewer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return ($res && mysqli_fetch_assoc($res)) ? true : false;
}
