<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');
include('includes/security.php');
include('includes/lang.php');

function has_admin(): bool {
    global $conn;
    $r = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE is_admin >= 1");
    return $r && (int)(mysqli_fetch_row($r)[0] ?? 0) > 0;
}

function setup_key_valid(string $submitted): bool {
    global $conn;
    $r = mysqli_query($conn, "SELECT config_value FROM app_config WHERE config_key='admin_setup_key'");
    if (!$r) return false;
    $row = mysqli_fetch_assoc($r);
    $key = $row['config_value'] ?? '';
    return $key !== '' && hash_equals($key, $submitted);
}

$google_enabled = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '';

if(isset($_POST['register'])){
    csrf_validate();
    $name  = trim($_POST['full_name']);
    $email_raw = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $setup_key = trim($_POST['setup_key'] ?? '');

    // Server-side password validation
    if (strlen($password_raw) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!validate_email_before_send($email_raw)['ok']) {
        $error = "That email address is not valid or its domain can't receive mail. Please double-check it and try again.";
    } else {
        // Check duplicate email using prepared statement
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt, "s", $email_raw);
        mysqli_stmt_execute($stmt);
        $check_email = mysqli_stmt_get_result($stmt);

        if ($check_email && mysqli_num_rows($check_email) > 0) {
            $error = "An account with this email already exists.";
        } elseif ($setup_key !== '') {
            // Admin bootstrap — use a transaction to prevent TOCTOU race condition
            mysqli_begin_transaction($conn);
            try {
                // Lock the users table to prevent concurrent admin creation
                mysqli_query($conn, "LOCK TABLES users WRITE, app_config WRITE");

                if (has_admin()) {
                    mysqli_query($conn, "UNLOCK TABLES");
                    mysqli_rollback($conn);
                    $error = "An admin account already exists. The setup key is no longer valid.";
                } elseif (!setup_key_valid($setup_key)) {
                    mysqli_query($conn, "UNLOCK TABLES");
                    mysqli_rollback($conn);
                    $error = "Invalid admin setup key. Please check and try again.";
                } else {
                    $pass = password_hash($password_raw, PASSWORD_DEFAULT);
                    $stmt2 = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, is_admin, status, email_verified) VALUES (?, ?, ?, 2, 1, 1)");
                    mysqli_stmt_bind_param($stmt2, "sss", $name, $email_raw, $pass);

                    if (mysqli_stmt_execute($stmt2)) {
                        mysqli_query($conn, "DELETE FROM app_config WHERE config_key='admin_setup_key'");
                        mysqli_query($conn, "UNLOCK TABLES");
                        mysqli_commit($conn);
                        header("Location: login.php?setup=admin");
                        exit();
                    } else {
                        mysqli_query($conn, "UNLOCK TABLES");
                        mysqli_rollback($conn);
                        $error = "Registration failed. Please try again.";
                    }
                }
            } catch (Exception $e) {
                mysqli_query($conn, "UNLOCK TABLES");
                mysqli_rollback($conn);
                $error = "Registration failed. Please try again.";
            }
        } else {
            // Regular landlord registration
            $pass = password_hash($password_raw, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 86400); // 24h

            $stmt3 = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, email_verified, verify_token, verify_expires) VALUES (?, ?, ?, 0, ?, ?)");
            mysqli_stmt_bind_param($stmt3, "sssss", $name, $email_raw, $pass, $token, $expires);

            if (mysqli_stmt_execute($stmt3)) {
                $_SESSION['verify_pending_email'] = $email_raw;
                $mailResult = send_verification_email($email_raw, $name, $token);
                $loc = 'verify_pending.php?email=' . urlencode($email_raw);
                if ($mailResult['ok'] === false && $mailResult['info'] !== 'dev') {
                    $loc .= '&resend=failed';
                    if ($mailResult['info'] === 'brevo ip not authorized') {
                        $loc .= '&why=ip_auth';
                    }
                }
                header("Location: $loc");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root{--brand:#0d9488;--brand2:#14b8a6;--ink:#0f172a;--muted:#64748b;--line:#e2e8f0;--bg:#eef2f7}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;background:var(--bg)}

        /* LEFT BRAND PANEL */
        .auth-left{flex:1.15;position:relative;display:flex;flex-direction:column;justify-content:center;padding:56px 64px;color:#fff;overflow:hidden;background:#0f172a}
        .auth-left-bg{position:absolute;inset:0;background:url('images/IMG_7172.JPG') center/cover no-repeat;transform:scale(1.05)}
        .auth-left-overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(15,23,42,.95) 0%,rgba(15,23,42,.78) 40%,rgba(13,148,136,.55) 100%)}
        .auth-brand{position:relative;z-index:2;display:inline-flex;align-items:center;gap:12px;align-self:flex-start;margin-bottom:auto}
        .auth-brand .brand-icon{width:42px;height:42px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;box-shadow:0 4px 18px rgba(13,148,136,.45)}
        .auth-brand .brand-name{font-size:20px;font-weight:800;letter-spacing:-.4px}
        .auth-brand .brand-name span{color:#2dd4bf}
        .auth-left-content{position:relative;z-index:2;max-width:440px}
        .eyebrow{display:inline-block;background:rgba(45,212,191,.12);border:1px solid rgba(45,212,191,.3);color:#5eead4;padding:6px 14px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:22px}
        .auth-left-content h2{font-size:clamp(26px,2.6vw,36px);font-weight:800;line-height:1.2;letter-spacing:-.8px;margin-bottom:16px}
        .auth-left-content h2 span{color:#2dd4bf}
        .auth-left-content>p{color:rgba(255,255,255,.65);font-size:15px;line-height:1.75;margin-bottom:38px}
        .auth-left .features{display:grid;gap:16px}
        .auth-left .features li{list-style:none;display:flex;gap:16px;align-items:flex-start}
        .auth-left .features li>i{width:46px;height:46px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#2dd4bf;background:rgba(13,148,136,.28);border:1px solid rgba(45,212,191,.2);border-radius:12px;font-size:18px}
        .auth-left .features li strong{display:block;font-size:15px;font-weight:700;color:#f1f5f9;margin-bottom:2px}
        .auth-left .features li small{font-size:13px;color:rgba(255,255,255,.6);line-height:1.5}
        .auth-quote{position:relative;z-index:2;margin-top:auto;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);backdrop-filter:blur(12px);border-radius:16px;padding:20px 24px;max-width:440px}
        .auth-quote .quote-stars{color:#fbbf24;font-size:13px;letter-spacing:2px;margin-bottom:8px}
        .auth-quote p{font-size:14px;line-height:1.7;color:rgba(255,255,255,.85)}
        .auth-quote .quote-author{font-size:12px;color:rgba(255,255,255,.5);margin-top:10px;font-weight:600}

        /* RIGHT FORM PANEL */
        .auth-right{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 40px}
        .auth-card{width:100%;max-width:440px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:40px 40px 36px;box-shadow:0 20px 50px rgba(15,23,42,.08)}
        .auth-card .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;margin-bottom:28px;transition:color .2s}
        .auth-card .back-link:hover{color:var(--brand)}
        .auth-card h1{font-size:26px;font-weight:800;color:var(--ink);margin-bottom:6px;letter-spacing:-.5px}
        .auth-card .subtitle{color:var(--muted);font-size:14px;margin-bottom:30px;line-height:1.6}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .error-msg i{font-size:16px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:7px;text-transform:uppercase;letter-spacing:.4px}
        .input-wrapper{position:relative}
        .input-wrapper>i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none;transition:color .2s}
        .input-wrapper .pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:none;background:transparent;color:#94a3b8;cursor:pointer;border-radius:8px;font-size:15px;transition:all .2s}
        .input-wrapper .pw-toggle:hover{color:var(--brand);background:rgba(13,148,136,.08)}
        .form-group input{width:100%;padding:13px 44px;border:1.5px solid var(--line);border-radius:12px;font-size:14.5px;font-family:inherit;background:#f8fafc;color:#0f172a;transition:all .22s}
        .form-group input::placeholder{color:#a8b3c0}
        .form-group input:focus{outline:none;border-color:var(--brand);background:#fff;box-shadow:0 0 0 4px rgba(13,148,136,.12)}
        .setup-hint{font-size:12px;color:#94a3b8;margin-top:7px;line-height:1.5}
        .setup-toggle-row{display:flex;align-items:center;justify-content:center;margin:4px 0 16px}
        .check{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--muted);cursor:pointer;user-select:none;line-height:1.5}
        .check input{width:16px;height:16px;accent-color:var(--brand);cursor:pointer;flex-shrink:0;margin:0}
        .check a{color:var(--brand);font-weight:600;text-decoration:none}
        .check a:hover{text-decoration:underline}
        .btn-submit{width:100%;padding:15px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;box-shadow:0 6px 18px rgba(13,148,136,.3);display:flex;align-items:center;justify-content:center;gap:9px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(13,148,136,.4)}
        .btn-submit:active{transform:translateY(0)}
        .divider{display:flex;align-items:center;gap:14px;margin:26px 0;color:#94a3b8;font-size:12px;font-weight:500}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line)}
        .btn-google{width:100%;display:flex;align-items:center;justify-content:center;gap:10px;padding:13px;background:#fff;border:1.5px solid var(--line);border-radius:12px;font-size:14px;font-weight:600;font-family:inherit;color:#0f172a;cursor:pointer;text-decoration:none;transition:all .2s}
        .btn-google:hover{background:#f8fafc;border-color:#cbd5e1;transform:translateY(-1px);box-shadow:0 6px 16px rgba(15,23,42,.06)}
        .auth-footer{text-align:center;margin-top:28px;font-size:14px;color:var(--muted)}
        .auth-footer a{color:var(--brand);text-decoration:none;font-weight:700}
        .auth-footer a:hover{text-decoration:underline}
        .auth-card .secure-note{margin-top:22px;padding:12px 14px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;color:#0f766e;font-size:12px;display:flex;align-items:center;gap:8px}
        .auth-card .secure-note i{font-size:15px}
        .lang-drop{position:fixed;top:20px;right:24px;z-index:1200}
        .lang-pill{display:inline-flex;align-items:center;gap:8px;color:#fff;background:#0f172a;border:1px solid rgba(255,255,255,.14);border-radius:50px;padding:9px 16px;font-weight:700;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;transition:all .2s;box-shadow:0 6px 20px rgba(0,0,0,.2)}
        .lang-pill:hover{background:#1e293b;border-color:rgba(45,212,191,.4)}
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
        .mobile-brand{display:none;text-align:center;margin-bottom:30px}
        .mobile-brand .logo{width:54px;height:54px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:14px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:22px;color:#fff;margin:0 auto 14px;box-shadow:0 6px 18px rgba(13,148,136,.35)}
        .mobile-brand h3{font-size:20px;font-weight:800;color:var(--ink)}
        @media(max-width:1024px){.auth-left{display:none}}
        @media(max-width:768px){
            body{flex-direction:column;background:#f1f5f9}
            .auth-left{display:none}
            .mobile-brand{display:block}
            .auth-right{padding:24px 16px}
            .auth-card{padding:32px 24px;border-radius:16px}
        }
    </style>
</head>
<body>
    <div class="auth-left">
    <div class="auth-left-bg"></div>
    <div class="auth-left-overlay"></div>
    <div class="auth-brand">
        <div class="brand-icon">AR</div>
        <div class="brand-name">Adama<span>Rent</span></div>
    </div>
    <div class="auth-left-content">
        <div class="eyebrow"><?php echo t('eyebrow'); ?></div>
        <h2><?php echo t('register_left_title1'); ?><span><?php echo t('register_left_title2'); ?></span><?php echo t('register_left_title3'); ?></h2>
        <p><?php echo t('register_left_desc'); ?></p>
        <ul class="features">
            <li><i class="fa-solid fa-house"></i><span><strong><?php echo t('feat_unlimited'); ?></strong><small><?php echo t('feat_unlimited_s'); ?></small></span></li>
            <li><i class="fa-solid fa-chart-line"></i><span><strong><?php echo t('feat_reach'); ?></strong><small><?php echo t('feat_reach_s'); ?></small></span></li>
            <li><i class="fa-solid fa-user-shield"></i><span><strong><?php echo t('feat_free'); ?></strong><small><?php echo t('feat_free_s'); ?></small></span></li>
        </ul>
    </div>
    <div class="auth-quote">
        <div class="quote-stars">★★★★★</div>
        <p><?php echo t('quote_text'); ?></p>
        <div class="quote-author"><?php echo t('quote_author'); ?></div>
    </div>
</div>
    <div class="auth-right">
        <div class="auth-card">
            <div class="mobile-brand">
                <div class="logo">AR</div>
                <h3>AdamaRent</h3>
            </div>
            <a href="Home.php" class="back-link"><i class="fas fa-arrow-left"></i> <?php echo t('back_home'); ?></a>
            <h1><?php echo t('register_title'); ?></h1>
            <p class="subtitle"><?php echo t('register_subtitle'); ?></p>

            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo tout($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label><?php echo t('full_name'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" placeholder="<?php echo t('full_name_ph'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('email_address'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="<?php echo t('email_ph'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('password'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="<?php echo t('pw_placeholder_signup'); ?>" required minlength="6">
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Show password"><i class="fas fa-eye" id="pwIcon"></i></button>
                    </div>
                </div>
                <div class="form-group" id="setupKeyGroup" style="display:none">
                    <label><?php echo t('admin_setup_key'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="text" name="setup_key" placeholder="<?php echo t('admin_key_ph'); ?>">
                    </div>
                    <div class="setup-hint"><?php echo t('admin_key_hint'); ?></div>
                </div>
                <div class="setup-toggle-row">
                    <label class="check">
                        <input type="checkbox" id="setupKeyToggle"> <?php echo t('im_admin'); ?>
                    </label>
                </div>
                <div class="form-group">
                    <label class="check">
                        <input type="checkbox" name="agree_terms" required>
                        <?php echo t('agree_terms'); ?><a href="terms.php" target="_blank"><?php echo t('terms_service'); ?></a><?php echo t('agree_terms2'); ?><a href="privacy.php" target="_blank"><?php echo t('privacy_policy'); ?></a><?php echo t('agree_terms_suffix'); ?>
                    </label>
                </div>
                <button type="submit" name="register" class="btn-submit"><i class="fas fa-user-plus"></i> <?php echo t('reg_btn'); ?></button>
            </form>

            <?php if($google_enabled): ?>
                <div class="divider"><?php echo t('or_continue'); ?></div>
                <a href="google_login.php" class="btn-google">
                    <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.2 29.5 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.2 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.5 0 10.5-2.1 14.2-5.6l-6.6-5.6C29.5 34.4 26.9 36 24 36c-5.2 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.3 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1c.3 1.2.4 2.5.4 3.9s-.2 2.7-.4 3.9c-1.3 5.6-5.4 10.5-11 12.7l6.6 5.6C43.2 42.2 48 36 48 24c0-1.3-.1-2.7-.4-3.9L43.6 20.1z"/></svg>
                    <?php echo t('continue_google'); ?>
                </a>
            <?php endif; ?>

            <div class="auth-footer">
                <?php echo t('has_account'); ?> <a href="login.php"><?php echo t('sign_in_link'); ?></a>
            </div>
            <div class="secure-note"><i class="fas fa-lock"></i> <?php echo t('secure_reg_note'); ?></div>
        </div>
    </div>
    <script>
    document.getElementById('setupKeyToggle').addEventListener('change', function(){
        document.getElementById('setupKeyGroup').style.display = this.checked ? 'block' : 'none';
    });
    function togglePassword(){
        var pw = document.getElementById('password');
        var icon = document.getElementById('pwIcon');
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye-slash', show);
        icon.classList.toggle('fa-eye', !show);
    }
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
