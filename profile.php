<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
include('includes/lang.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
$uid = (int)$_SESSION['user_id'];

$notif = null; // ['type','title','message']
$err = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_validate();
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $phone2    = trim($_POST['phone2'] ?? '');
    $new_pass  = (string)($_POST['new_password'] ?? '');

    if($full_name === '' || $email === ''){
        $err = 'Full name and email are required.';
    } elseif($new_pass !== '' && strlen($new_pass) < 6){
        $err = 'New password must be at least 6 characters long.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? AND id <> ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $uid);
        mysqli_stmt_execute($stmt);
        $chk = mysqli_stmt_get_result($stmt);
        if($chk && mysqli_num_rows($chk) > 0){
            $err = 'An account with this email address already exists.';
        } else {
            $stmt2 = mysqli_prepare($conn, "UPDATE users SET full_name=?, email=?, phone=?, phone2=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "ssssi", $full_name, $email, $phone, $phone2, $uid);
            $upd = mysqli_stmt_execute($stmt2);
            if($upd){
                $_SESSION['user_name'] = $full_name;
                if($new_pass !== ''){
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt3 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt3, "si", $hash, $uid);
                    mysqli_stmt_execute($stmt3);
                    $notif = ['type' => 'success', 'title' => 'Account updated', 'message' => 'Your profile and password were updated successfully.'];
                } else {
                    $notif = ['type' => 'success', 'title' => 'Account updated', 'message' => 'Your profile was updated successfully.'];
                }
            } else {
                $err = 'Could not save your changes. Please try again.';
            }
        }
    }
}

$stmt4 = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt4, "i", $uid);
mysqli_stmt_execute($stmt4);
$res = mysqli_stmt_get_result($stmt4);
$user = $res ? mysqli_fetch_assoc($res) : null;
if(!$user){ header("Location: logout.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;display:flex;flex-direction:column}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,.15)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
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
        .lang-pill{display:inline-flex;align-items:center;gap:7px;color:#fff;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:50px;padding:8px 15px;font-weight:600;font-size:13px;text-decoration:none;transition:all .2s;cursor:pointer;font-family:'Poppins',sans-serif}
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

        .page{max-width:820px;margin:0 auto;width:100%;padding:30px 24px 60px;flex:1}
        .profile-head{display:flex;align-items:center;gap:16px;margin-bottom:24px}
        .profile-av{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;flex-shrink:0}
        .profile-head h1{font-size:22px;font-weight:800;color:#0f172a}
        .profile-head p{font-size:13px;color:#64748b;margin-top:3px}
        .profile-head .role-chip{display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
        .role-chip.admin{background:rgba(59,130,246,.12);color:#2563eb}
        .role-chip.landlord{background:rgba(13,148,136,.12);color:#0d9488}

        .card{background:#fff;border-radius:18px;border:1px solid #f1f5f9;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,.04);margin-bottom:20px}
        .card h3{font-size:15px;font-weight:800;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:8px}
        .card h3 i{color:#0d9488}
        .card .card-sub{font-size:13px;color:#94a3b8;margin-bottom:18px}

        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px}
        .input-wrap{position:relative}
        .input-wrap > i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px}
        .form-group input{width:100%;padding:12px 14px 12px 42px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s;background:#fff;color:#0f172a}
        .form-group input:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
        .form-hint{font-size:12px;color:#94a3b8;margin-top:5px}
        .btn-save{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .25s}
        .btn-save:hover{box-shadow:0 6px 20px rgba(13,148,136,.4);transform:translateY(-1px)}

        .err-box{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}

        @media(max-width:560px){
            .navbar{padding:12px 16px}
            .page{padding:16px;padding-bottom:48px}
            .card{padding:20px}
            .form-row{grid-template-columns:1fr}
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
            <a href="post_house.php" class="btn-accent"><i class="fas fa-plus"></i> <?php echo t('new_posts'); ?></a>
            <a href="manage_houses.php"><i class="fas fa-th-large"></i> <?php echo t('nav_dashboard'); ?></a>
        </div>
    </nav>

    <div class="page">
        <?php if($err): ?>
            <div class="err-box"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($err); ?></div>
        <?php endif; ?>

        <div class="profile-head">
            <div class="profile-av"><?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))); ?></div>
            <div>
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <div class="role-chip <?php echo (int)$user['is_admin'] >= 1 ? 'admin' : 'landlord'; ?>">
                    <i class="fas fa-circle" style="font-size:6px"></i> <?php echo (int)$user['is_admin'] >= 1 ? t('role_admin') : t('role_landlord'); ?>
                </div>
            </div>
        </div>

        <form method="POST">
                <?php echo csrf_field(); ?>
            <div class="card">
                <h3><i class="fas fa-user-pen"></i> <?php echo t('profile_info_title'); ?></h3>
                <p class="card-sub"><?php echo t('profile_info_sub'); ?></p>

                <div class="form-group">
                    <label><?php echo t('full_name'); ?></label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo t('email_address'); ?></label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('phone_primary'); ?></label>
                        <div class="input-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="<?php echo t('phone_placeholder'); ?>" pattern="[0-9+ ]{7,20}" title="Enter a valid phone number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('phone_additional'); ?> <span style="color:#94a3b8;font-weight:500"><?php echo t('profile_optional'); ?></span></label>
                        <div class="input-wrap">
                            <i class="fas fa-phone-volume"></i>
                            <input type="tel" name="phone2" value="<?php echo htmlspecialchars($user['phone2']); ?>" placeholder="<?php echo t('phone_placeholder'); ?>" pattern="[0-9+ ]{7,20}" title="Enter a valid phone number">
                        </div>
                    </div>
                </div>
                <div class="form-hint"><i class="fas fa-circle-info"></i> <?php echo t('profile_phone_hint'); ?></div>
            </div>

            <div class="card">
                <h3><i class="fas fa-lock"></i> <?php echo t('profile_password_title'); ?></h3>
                <p class="card-sub"><?php echo t('profile_password_sub'); ?></p>
                <div class="form-group">
                    <label><?php echo t('new_password'); ?></label>
                    <div class="input-wrap">
                        <i class="fas fa-key"></i>
                        <input type="password" name="new_password" placeholder="<?php echo t('new_password_ph'); ?>" minlength="6">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save"><i class="fas fa-save"></i> <?php echo t('save_changes'); ?></button>
        </form>
    </div>

    <script>
        <?php if($notif): ?>
        var msg = <?php echo json_encode($notif['message']); ?>;
        window.addEventListener('DOMContentLoaded', function(){ showToast(msg, <?php echo json_encode($notif['type']); ?>, <?php echo json_encode($notif['title']); ?>); });
        <?php endif; ?>
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
    <?php include(__DIR__ . '/includes/popup.php'); ?>
</body>
</html>