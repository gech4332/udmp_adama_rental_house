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


if(isset($_POST['register'])){
    csrf_validate();
    $name = trim($_POST['full_name']);
    $email_raw = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $confirm_raw = $_POST['confirm_password'] ?? '';
    $setup_key = trim($_POST['setup_key'] ?? '');

    // Server-side input validation
    if (mb_strlen($name) < 2) {
        $error = "Please enter your full name (at least 2 characters).";
    } elseif (strlen($password_raw) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($confirm_raw !== $password_raw) {
        $error = "Passwords do not match.";
    } elseif (!validate_email_before_send($email_raw)['ok']) {
        $error = "That email address is not valid or its domain can't receive mail. Please double-check it and try again.";
    } else {
        // Check duplicate email using prepared statement
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt, "s", $email_raw);
        mysqli_stmt_execute($stmt);
        $check_email = mysqli_stmt_get_result($stmt);

        if ($check_email && mysqli_num_rows($check_email) > 0) {
            // Anti-enumeration: respond identically to a successful sign-up so
            // the server never reveals whether the email is already registered.
            $_SESSION['verify_pending_email'] = $email_raw;
            header("Location: verify_pending.php?email=" . urlencode($email_raw));
            exit();
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
                        header("Location: admin_login.php?setup=admin");
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
    <title>Admin Sign Up - EthioRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;background:#f8fafc}
        .auth-left{flex:1;background:linear-gradient(135deg,#0f172a 0%,#052e16 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px;color:#fff;position:relative;overflow:hidden}
        .auth-left::before{content:'';position:absolute;width:400px;height:400px;border-radius:50%;background:rgba(7,137,48,.15);top:-80px;right:-80px}
        .auth-left::after{content:'';position:absolute;width:250px;height:250px;border-radius:50%;background:rgba(7,137,48,.1);bottom:-40px;left:-40px}
        .auth-left-content{position:relative;z-index:2;text-align:center;max-width:400px}
        .auth-left-content .logo{width:60px;height:60px;background:#078930;border-radius:16px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:24px;text-shadow:0 1px 3px rgba(0,0,0,.45);margin:0 auto 24px}
        .auth-left-content h2{font-size:28px;font-weight:800;margin-bottom:12px}
        .auth-left-content p{color:rgba(255,255,255,.7);font-size:15px;line-height:1.7}
        .auth-left .features{margin-top:40px;text-align:left}
        .auth-left .features li{list-style:none;padding:10px 0;color:rgba(255,255,255,.8);font-size:14px;display:flex;align-items:center;gap:12px}
        .auth-left .features li i{color:#FCDD09;font-size:16px}
        .auth-right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px}
        .auth-card{width:100%;max-width:400px}
        .auth-card h1{font-size:28px;font-weight:800;color:#0f172a;margin-bottom:6px;letter-spacing:-.5px}
        .auth-card .subtitle{color:#64748b;font-size:15px;margin-bottom:32px}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .error-msg i{font-size:16px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .input-wrapper{position:relative}
        .input-wrapper i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:16px}
        .form-group input{width:100%;padding:14px 14px 14px 44px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:inherit;transition:all .2s;background:#fff}
        .form-group input:focus{outline:none;border-color:#078930;box-shadow:0 0 0 3px rgba(7,137,48,.1)}
        .input-wrapper .pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:none;background:transparent;color:#9ca3af;cursor:pointer;border-radius:8px;font-size:15px;transition:all .2s}
        .input-wrapper .pw-toggle:hover{color:#078930;background:rgba(7,137,48,.08)}
        .setup-hint{font-size:12px;color:#94a3b8;margin-top:7px;line-height:1.5}
        label.check{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:400;color:#64748b;cursor:pointer;user-select:none;line-height:1.5;margin-bottom:0}
        label.check input{width:16px;height:16px;accent-color:#078930;cursor:pointer;flex-shrink:0;margin:0}
        label.check a{color:#078930;font-weight:600;text-decoration:none}
        label.check a:hover{text-decoration:underline}
        .btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#05642a,#078930);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;margin-top:8px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(7,137,48,.4)}
        .mobile-brand{display:none;text-align:center;margin-bottom:32px}
        .mobile-brand .logo{width:48px;height:48px;background:#078930;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:20px;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,.45);margin:0 auto 12px}
        .mobile-brand h3{font-size:18px;font-weight:800;color:#0f172a}
        @media(max-width:768px){
            body{flex-direction:column}
            .auth-left{display:none}
            .mobile-brand{display:block}
            .auth-right{padding:24px}
        }
    </style>
</head>
<body>
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="logo"><i class="fas fa-shield-halved"></i></div>
            <h2>Admin Portal</h2>
            <p>Secure access to the EthioRent administration console. Manage users, listings, and approvals.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Manage users and permissions</li>
                <li><i class="fas fa-check-circle"></i> Approve property listings</li>
                <li><i class="fas fa-check-circle"></i> View analytics and reports</li>
            </ul>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-card">
            <div class="mobile-brand">
                <div class="logo"><i class="fas fa-shield-halved"></i></div>
                <h3>Admin Portal</h3>
            </div>
            <h1>Create Admin Account</h1>
            <p class="subtitle">Create a separate administrator account</p>

            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo tout($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" placeholder="Your full name" required autocomplete="name">
                    </div>
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="admin@ethiorent.com" required autocomplete="email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Create a strong password" required minlength="6" autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Show password"><i class="fas fa-eye" id="pwIcon"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group" id="setupKeyGroup">
                    <label>Admin Setup Key</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="text" name="setup_key" placeholder="Paste admin setup key here">
                    </div>
                    <div class="setup-hint">Only needed if you want this account to become the first administrator.</div>
                </div>
                <div class="form-group">
                    <label class="check">
                        <input type="checkbox" name="agree_terms" required>
                        I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>.
                    </label>
                </div>
                <button type="submit" name="register" class="btn-submit"><i class="fas fa-user-plus"></i> Create Account</button>
            </form>
            <p class="subtitle" style="margin-top:20px;text-align:center">
                <a href="admin_login.php" style="color:#078930;font-weight:700;text-decoration:none"><i class="fas fa-right-to-bracket"></i> Already have an account? Sign in</a>
            </p>
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
    </script>
</body>
</html>
