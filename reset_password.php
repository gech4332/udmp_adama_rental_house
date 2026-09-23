<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include('includes/lang.php');

$token = (string)($_POST['token'] ?? ($_GET['token'] ?? ''));
$email = trim($_POST['email'] ?? ($_GET['email'] ?? ''));
$request_email = $email;

$error = '';
$valid = false;
$uid = 0;

if($token !== '' && $email !== ''){
    $stmt = mysqli_prepare($conn, "SELECT id, email, reset_expires FROM users WHERE email=? AND reset_token=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $email, $token);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if($res && ($user = mysqli_fetch_assoc($res))){
        if($user['reset_expires'] !== null && strtotime($user['reset_expires']) < time()){
            $error = t('rp_expired');
        } else {
            $valid = true;
            $uid = (int)$user['id'];
        }
    } else {
        $error = t('rp_invalid');
    }
}

if($valid && isset($_POST['reset'])){
    csrf_validate();
    // Re-validate the token on submission too, in case it was revoked meanwhile.
    $stmt = mysqli_prepare($conn, "SELECT id, reset_expires FROM users WHERE email=? AND reset_token=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $email, $token);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if(!$res || !($user = mysqli_fetch_assoc($res))){
        $valid = false;
        $error = t('rp_invalid');
    } elseif($user['reset_expires'] !== null && strtotime($user['reset_expires']) < time()){
        $valid = false;
        $error = t('rp_expired');
    } else {
        $uid = (int)$user['id'];
    }
}

if($valid && isset($_POST['reset'])){
    $pass = (string)($_POST['password'] ?? '');
    if(strlen($pass) < 6){
        $error = t('pw_too_short');
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $up = mysqli_prepare($conn, "UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        mysqli_stmt_bind_param($up, "si", $hash, $uid);
        mysqli_stmt_execute($up);
        // Clear brute-force counters so the user can sign in immediately.
        clear_login_failures($email);
        session_regenerate_id(true);
        header("Location: login.php?reset=done");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('rp_title'); ?> - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root{--brand:#0d9488;--brand2:#14b8a6;--ink:#0f172a;--muted:#64748b;--line:#e2e8f0}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#eef2f7 0%,#f0fdfa 100%);padding:24px}
        .card{width:100%;max-width:460px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:44px 44px 38px;box-shadow:0 20px 50px rgba(15,23,42,.08)}
        .icon-wrap{width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;margin-bottom:20px;box-shadow:0 8px 22px rgba(13,148,136,.35)}
        .icon-wrap i{color:#fff;font-size:24px}
        h1{font-size:25px;font-weight:800;color:var(--ink);margin-bottom:8px;letter-spacing:-.5px}
        .subtitle{color:var(--muted);font-size:14px;margin-bottom:26px;line-height:1.6}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:7px;text-transform:uppercase;letter-spacing:.4px}
        .input-wrapper{position:relative}
        .input-wrapper>i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none}
        .input-wrapper .pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:none;background:transparent;color:#94a3b8;cursor:pointer;border-radius:8px;font-size:15px;transition:all .2s}
        .input-wrapper .pw-toggle:hover{color:var(--brand);background:rgba(13,148,136,.08)}
        .form-group input{width:100%;padding:13px 44px;border:1.5px solid var(--line);border-radius:12px;font-size:14.5px;font-family:inherit;background:#f8fafc;color:#0f172a;transition:all .22s}
        .form-group input::placeholder{color:#a8b3c0}
        .form-group input:focus{outline:none;border-color:var(--brand);background:#fff;box-shadow:0 0 0 4px rgba(13,148,136,.12)}
        .btn-submit{width:100%;padding:15px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;box-shadow:0 6px 18px rgba(13,148,136,.3);display:flex;align-items:center;justify-content:center;gap:9px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(13,148,136,.4)}
        .back{margin-top:22px;text-align:center;font-size:13px;color:#94a3b8}
        .back a{color:var(--brand);font-weight:600;text-decoration:none}
        .back a:hover{text-decoration:underline}
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
        <div class="icon-wrap"><i class="fas fa-lock-open"></i></div>

        <?php if(!$valid): ?>
            <h1><?php echo t('rp_title'); ?></h1>
            <p class="subtitle"><?php echo t('rp_invalid'); ?></p>
            <?php if($error !== ''): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo tout($error); ?></div>
            <?php endif; ?>
            <a href="forgot_password.php" class="btn-submit" style="text-align:center;text-decoration:none;display:flex"><i class="fas fa-key"></i> <?php echo t('fp_btn'); ?></a>
        <?php else: ?>
            <h1><?php echo t('rp_title'); ?></h1>
            <p class="subtitle"><?php echo t('rp_subtitle'); ?></p>

            <?php if($error !== ''): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo tout($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($request_email); ?>">
                <div class="form-group">
                    <label><?php echo t('rp_new_pw_label'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="<?php echo t('rp_new_pw_ph'); ?>" required minlength="6">
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Show password"><i class="fas fa-eye" id="pwIcon"></i></button>
                    </div>
                </div>
                <button type="submit" name="reset" class="btn-submit"><i class="fas fa-check"></i> <?php echo t('rp_btn'); ?></button>
            </form>
        <?php endif; ?>

        <div class="back"><a href="login.php"><?php echo t('rp_back'); ?></a></div>
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