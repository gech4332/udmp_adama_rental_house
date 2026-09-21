<?php 
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');
include('includes/security.php');
include('includes/lang.php');

$verification_warning = false;
$verification_email = '';

if(isset($_POST['login'])){
    csrf_validate();
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if (login_locked_out($email)) {
        $error = login_lockout_message();
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if($res && ($user = mysqli_fetch_assoc($res))){
        if(password_verify($pass, $user['password'])){
            clear_login_failures($email);
            session_regenerate_id(true);
            // Email verification gate
            if((int)$user['email_verified'] === 0){
                $_SESSION['verify_pending_email'] = $user['email'];
                header("Location: verify_pending.php?email=" . urlencode($user['email']));
                exit();
            } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Check for a pending admin invite for this user
            $uid = (int)$user['id'];
            $inv_stmt = mysqli_prepare($conn, "SELECT * FROM admin_invites WHERE user_id=? AND status='pending' LIMIT 1");
            mysqli_stmt_bind_param($inv_stmt, "i", $uid);
            mysqli_stmt_execute($inv_stmt);
            $check_invite = mysqli_stmt_get_result($inv_stmt);
            if(mysqli_num_rows($check_invite) > 0){
                $_SESSION['pending_admin_key'] = 1;
                header("Location: admin_key.php");
                exit();
            }

            if($user['is_admin'] >= 1){
                $_SESSION['is_admin'] = (int)$user['is_admin'];
                header("Location: admin_panel.php");
            } else {
                // If a redirect target was requested (e.g. rent flow), go there
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
                $safe_redirect = preg_replace('/[^a-zA-Z0-9_?&=.\-]/', '', $redirect);
                if(!empty($safe_redirect) && strpos($safe_redirect, 'rent_request.php') !== false){
                    header("Location: " . $safe_redirect);
                } else {
                    header("Location: index.php");
                }
            }
            exit();
            }
        } else {
            record_login_failure($email);
            $error = "Invalid email or password.";
        }
    } else {
        record_login_failure($email);
        $error = "Invalid email or password.";
    }
    }
}

// Google sign-in availability + flash errors
$google_enabled = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '';
$google_err = '';
if(isset($_GET['google'])){
    $google_err = $_GET['google'] === 'denied'
        ? 'Sign-in with Google was cancelled.'
        : ($_GET['google'] === 'unverified'
            ? 'Your Google account email is not verified. Please verify it with Google and try again.'
            : 'Google sign-in failed. Please try again, or use email &amp; password.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AdamaRent</title>
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

        /* RIGHT FORM PANEL */
        .auth-right{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 40px}
        .auth-card{width:100%;max-width:440px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:40px 40px 36px;box-shadow:0 20px 50px rgba(15,23,42,.08)}
        .auth-card .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;margin-bottom:28px;transition:color .2s}
        .auth-card .back-link:hover{color:var(--brand)}
        .auth-card h1{font-size:26px;font-weight:800;color:var(--ink);margin-bottom:6px;letter-spacing:-.5px}
        .auth-card .subtitle{color:var(--muted);font-size:14px;margin-bottom:30px;line-height:1.6}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .error-msg i{font-size:16px}
        .info-msg{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .info-msg i{font-size:16px}
        .warning-msg{background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:14px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;line-height:1.6}
        .warning-msg i{font-size:16px;margin-top:2px}
        .warning-msg a{color:#d97706;font-weight:700}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:7px;text-transform:uppercase;letter-spacing:.4px}
        .input-wrapper{position:relative}
        .input-wrapper>i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none;transition:color .2s}
        .input-wrapper .pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:none;background:transparent;color:#94a3b8;cursor:pointer;border-radius:8px;font-size:15px;transition:all .2s}
        .input-wrapper .pw-toggle:hover{color:var(--brand);background:rgba(13,148,136,.08)}
        .form-group input{width:100%;padding:13px 44px;border:1.5px solid var(--line);border-radius:12px;font-size:14.5px;font-family:inherit;background:#f8fafc;color:#0f172a;transition:all .22s}
        .form-group input::placeholder{color:#a8b3c0}
        .form-group input:focus{outline:none;border-color:var(--brand);background:#fff;box-shadow:0 0 0 4px rgba(13,148,136,.12)}
        .form-group input:-webkit-autofill,.form-group input:-webkit-autofill:hover,.form-group input:-webkit-autofill:focus{-webkit-box-shadow:0 0 0 1000px #fff inset;-webkit-text-fill-color:#0f172a;border-color:var(--line)}
        .input-wrapper:focus-within>i{color:var(--brand)}
        @keyframes riseIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .auth-card{animation:riseIn .5s cubic-bezier(.2,.7,.3,1) both}
        .form-row{display:flex;align-items:center;justify-content:space-between;margin:2px 0 20px}
        .check{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--muted);cursor:pointer;user-select:none;line-height:1.5}
        .check input{width:16px;height:16px;accent-color:var(--brand);cursor:pointer;flex-shrink:0;margin:0}
        .form-row a{color:var(--brand);font-size:13px;font-weight:600;text-decoration:none}
        .form-row a:hover{text-decoration:underline}
        .btn-submit{width:100%;padding:15px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;box-shadow:0 6px 18px rgba(13,148,136,.3);display:flex;align-items:center;justify-content:center;gap:9px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(13,148,136,.4)}
        .btn-submit:active{transform:translateY(0)}
        .divider{display:flex;align-items:center;gap:14px;margin:26px 0;color:#94a3b8;font-size:12px;font-weight:500}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line)}
        .btn-google{width:100%;display:flex;align-items:center;justify-content:center;gap:10px;padding:13px;background:#fff;border:1.5px solid var(--line);border-radius:12px;font-size:14px;font-weight:600;font-family:inherit;color:#0f172a;cursor:pointer;text-decoration:none;transition:all .2s}
        .btn-google:hover{background:#f8fafc;border-color:#cbd5e1;transform:translateY(-1px);box-shadow:0 6px 16px rgba(15,23,42,.06)}
        .auth-footer{text-align:center;margin-top:28px}
        .auth-footer p{color:var(--muted);font-size:14px;margin-bottom:12px}
        .auth-footer a.footer-link{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;padding:13px;background:#fff;border:1.5px solid var(--line);border-radius:12px;color:var(--brand);text-decoration:none;font-size:14px;font-weight:700;font-family:inherit;transition:all .2s}
        .auth-footer a.footer-link:hover{background:#f0fdfa;border-color:var(--brand);transform:translateY(-1px);box-shadow:0 6px 16px rgba(13,148,136,.12)}
        .auth-card .secure-note{margin-top:22px;padding:12px 14px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;color:#0f766e;font-size:12px;display:flex;align-items:center;gap:8px}
        .auth-card .secure-note i{font-size:15px}
        .resend-line{text-align:center;margin-top:16px;font-size:12.5px}
        .resend-line a{color:#94a3b8;text-decoration:none;font-weight:600;transition:color .2s}
        .resend-line a:hover{color:var(--brand)}
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
        @media(max-width:1024px){
            .auth-left{display:none}
            .mobile-brand{display:block}
        }
        @media(max-width:768px){
            body{flex-direction:column;background:#f1f5f9}
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
        <h2><?php echo t('login_left_title1'); ?><span><?php echo t('login_left_title2'); ?></span><?php echo t('login_left_title3'); ?></h2>
        <p><?php echo t('login_left_desc'); ?></p>
        <ul class="features">
            <li><i class="fa-solid fa-house"></i><span><strong><?php echo t('feat_mng'); ?></strong><small><?php echo t('feat_mng_s'); ?></small></span></li>
            <li><i class="fa-solid fa-chart-line"></i><span><strong><?php echo t('feat_reach'); ?></strong><small><?php echo t('feat_reach_s'); ?></small></span></li>
            <li><i class="fa-solid fa-user-shield"></i><span><strong><?php echo t('feat_secure'); ?></strong><small><?php echo t('feat_secure_s'); ?></small></span></li>
        </ul>
    </div>
</div>
    <div class="auth-right">
        <div class="auth-card">
            <div class="mobile-brand">
                <div class="logo">AR</div>
                <h3>AdamaRent</h3>
            </div>
            <a href="Home.php" class="back-link"><i class="fas fa-arrow-left"></i> <?php echo t('back_home'); ?></a>
            <h1><?php echo t('login_title'); ?></h1>
            <p class="subtitle"><?php echo t('login_subtitle'); ?></p>

            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo tout($error); ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['resend']) && $_GET['resend'] === 'already'): ?>
                <div class="info-msg"><i class="fas fa-check-circle"></i> <?php echo tout('This email is already verified — sign in below.'); ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['reset']) && $_GET['reset'] === 'done'): ?>
                <div class="info-msg"><i class="fas fa-check-circle"></i> <?php echo tout('Your password has been reset. Please sign in.'); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label><?php echo t('email_address'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="<?php echo t('email_ph'); ?>" required autocomplete="email">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo t('password'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="<?php echo t('pw_placeholder'); ?>" required autocomplete="current-password">
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Show password"><i class="fas fa-eye" id="pwIcon"></i></button>
                    </div>
                </div>
                <div class="form-row">
                    <label class="check"><input type="checkbox" name="remember"> <?php echo t('remember_me'); ?></label>
                    <a href="forgot_password.php"><?php echo t('forgot_password'); ?></a>
                </div>
                <button type="submit" name="login" class="btn-submit"><i class="fas fa-right-to-bracket"></i> <?php echo t('sign_in'); ?></button>
                <p class="resend-line">
                    <a href="resend_verification.php"><?php echo t('resend_verify_link'); ?></a>
                </p>
            </form>

            <?php if($google_enabled): ?>
                <div class="divider"><?php echo t('or_continue'); ?></div>
                <a href="google_login.php" class="btn-google">
                    <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.2 29.5 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.2 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.5 0 10.5-2.1 14.2-5.6l-6.6-5.6C29.5 34.4 26.9 36 24 36c-5.2 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.3 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1c.3 1.2.4 2.5.4 3.9s-.2 2.7-.4 3.9c-1.3 5.6-5.4 10.5-11 12.7l6.6 5.6C43.2 42.2 48 36 48 24c0-1.3-.1-2.7-.4-3.9L43.6 20.1z"/></svg>
                    <?php echo t('continue_google'); ?>
                </a>
            <?php endif; ?>

            <?php if($google_err !== ''): ?>
                <div class="error-msg" style="margin-top:14px"><i class="fas fa-circle-exclamation"></i> <?php echo tout($google_err); ?></div>
            <?php endif; ?>

            <div class="auth-footer">
                <p><?php echo t('no_account'); ?></p>
                <a href="register.php" class="footer-link"><i class="fas fa-user-plus"></i> <?php echo t('create_one'); ?></a>
            </div>
            <div class="secure-note"><i class="fas fa-lock"></i> <?php echo t('secure_note'); ?></div>
        </div>
    </div>
    <script>
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
