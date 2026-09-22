<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');
include('includes/security.php');
include('includes/lang.php');

$error = '';
$message = '';
$dev_link = '';

if(isset($_POST['forgot'])){
    csrf_validate();
    $email = trim($_POST['email'] ?? '');

    if($email === '' || !validate_email_before_send($email)['ok']){
        // Format-level only — never reveals whether an account exists.
        $error = t('fp_invalid_email');
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if($res && ($user = mysqli_fetch_assoc($res))){
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $uid = (int)$user['id'];
            $up = mysqli_prepare($conn, "UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
            mysqli_stmt_bind_param($up, "ssi", $token, $expires, $uid);
            mysqli_stmt_execute($up);

            send_password_reset_email($email, $user['full_name'], $token);

            // In dev mode (no mail server) surface the link so the flow is testable.
            if(!mail_env_is_configured()){
                $dev_link = 'reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token);
            }
        }
        // Always respond identically whether or not the email is registered.
        $message = t('fp_sent');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('fp_title'); ?> - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root{--brand:#0d9488;--brand2:#14b8a6;--ink:#0f172a;--muted:#64748b;--line:#e2e8f0}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#eef2f7 0%,#f0fdfa 100%);padding:24px}
        .card{width:100%;max-width:460px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:44px 44px 38px;box-shadow:0 20px 50px rgba(15,23,42,.08)}
        .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;margin-bottom:30px;transition:color .2s}
        .back-link:hover{color:var(--brand)}
        .icon-wrap{width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;margin-bottom:20px;box-shadow:0 8px 22px rgba(13,148,136,.35)}
        .icon-wrap i{color:#fff;font-size:24px}
        h1{font-size:25px;font-weight:800;color:var(--ink);margin-bottom:8px;letter-spacing:-.5px}
        .subtitle{color:var(--muted);font-size:14px;margin-bottom:26px;line-height:1.6}
        .msg{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;line-height:1.6;margin-bottom:20px}
        .msg.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
        .msg.err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:7px;text-transform:uppercase;letter-spacing:.4px}
        .input-wrapper{position:relative}
        .input-wrapper>i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none}
        .form-group input{width:100%;padding:13px 44px;border:1.5px solid var(--line);border-radius:12px;font-size:14.5px;font-family:inherit;background:#f8fafc;color:#0f172a;transition:all .22s}
        .form-group input::placeholder{color:#a8b3c0}
        .form-group input:focus{outline:none;border-color:var(--brand);background:#fff;box-shadow:0 0 0 4px rgba(13,148,136,.12)}
        .btn-submit{width:100%;padding:15px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;box-shadow:0 6px 18px rgba(13,148,136,.3);display:flex;align-items:center;justify-content:center;gap:9px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(13,148,136,.4)}
        .dev-note{margin-top:20px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:12px;padding:12px 16px;font-size:13px;line-height:1.6}
        .dev-note a{color:#d97706;font-weight:700;word-break:break-all}
        .secure-note{margin-top:22px;padding:12px 14px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;color:#0f766e;font-size:12px;display:flex;align-items:center;gap:8px}
        .secure-note i{font-size:15px}
        .lang-drop{position:fixed;top:20px;right:24px;z-index:1200}
        .lang-pill{display:inline-flex;align-items:center;gap:8px;color:#0f172a;background:#fff;border:1px solid var(--line);border-radius:50px;padding:9px 16px;font-weight:700;font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;transition:all .2s;box-shadow:0 6px 20px rgba(0,0,0,.08)}
        .lang-pill:hover{border-color:rgba(13,148,136,.4)}
        .lang-pill .lg-code{color:var(--brand)}
        .lang-pill .chev{margin-left:3px;font-size:10px;color:#94a3b8}
        .lang-menu{position:absolute;top:calc(100% + 10px);right:0;min-width:200px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:6px;box-shadow:0 20px 40px rgba(15,23,42,.15);opacity:0;visibility:hidden;transform:translateY(-6px);transition:all .22s cubic-bezier(.34,1.56,.64,1);z-index:1201}
        .lang-drop.open .lang-menu{opacity:1;visibility:visible;transform:translateY(0)}
        .lang-menu a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:#334155;text-decoration:none;font-size:13.5px;font-weight:600;transition:background .15s}
        .lang-menu a:hover{background:#f0fdfa}
        .lang-menu a.active{background:rgba(13,148,136,.1);color:var(--brand)}
        .lang-menu a .lg-badge{width:30px;height:30px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
        .lang-menu a.active .lg-badge{background:rgba(13,148,136,.15);color:var(--brand)}
        .lang-menu a .lg-check{margin-left:auto;color:var(--brand);font-size:12px}
        @media(max-width:480px){
            .card{padding:32px 24px}
        }
    </style>
</head>
<body>
    <div class="lang-drop" id="langDrop">
        <button type="button" class="lang-pill" onclick="toggleLangMenu(this)"><span class="lg-code"><?php echo strtoupper($lang); ?></span> <?php echo lang_name($lang); ?><i class="fas fa-chevron-down chev"></i></button>
        <div class="lang-menu">
            <a href="<?php echo lang_switch_url('en'); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"><span class="lg-badge">EN</span> English<i class="fas fa-check lg-check" style="visibility:<?php echo $lang === 'en' ? 'visible' : 'hidden'; ?>"></i></a>
            <a href="<?php echo lang_switch_url('am'); ?>" class="<?php echo $lang === 'am' ? 'active' : ''; ?>"><span class="lg-badge">አማ</span> አማርኛ<i class="fas fa-check lg-check" style="visibility:<?php echo $lang === 'am' ? 'visible' : 'hidden'; ?>"></i></a>
            <a href="<?php echo lang_switch_url('om'); ?>" class="<?php echo $lang === 'om' ? 'active' : ''; ?>"><span class="lg-badge">OM</span> Afaan Oromoo<i class="fas fa-check lg-check" style="visibility:<?php echo $lang === 'om' ? 'visible' : 'hidden'; ?>"></i></a>
        </div>
    </div>

    <div class="card">
        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> <?php echo t('fp_back'); ?></a>
        <div class="icon-wrap"><i class="fas fa-key"></i></div>
        <h1><?php echo t('fp_title'); ?></h1>
        <p class="subtitle"><?php echo t('fp_subtitle'); ?></p>

        <?php if($error !== ''): ?>
            <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo tout($error); ?></div>
        <?php endif; ?>

        <?php if($message !== ''): ?>
            <div class="msg ok"><i class="fas fa-paper-plane"></i> <?php echo tout($message); ?></div>

            <?php if($dev_link !== ''): ?>
                <div class="dev-note"><i class="fas fa-flask"></i> <strong>DEV MODE</strong> — email server not configured. Your reset link:<br>
                    <a href="<?php echo $dev_link; ?>"><?php echo $dev_link; ?></a></div>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label><?php echo t('email_address'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="<?php echo t('email_ph'); ?>" required>
                    </div>
                </div>
                <button type="submit" name="forgot" class="btn-submit"><i class="fas fa-paper-plane"></i> <?php echo t('fp_btn'); ?></button>
            </form>
        <?php endif; ?>

        <div class="secure-note"><i class="fas fa-lock"></i> <?php echo t('secure_note'); ?></div>
    </div>

    <script>
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