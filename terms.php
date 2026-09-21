<?php include('includes/session_config.php'); session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terms of Service - AdamaRent</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh;display:flex;flex-direction:column}
.page-wrap{max-width:800px;margin:0 auto;padding:60px 24px;flex:1}
.back-link{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13px;font-weight:500;margin-bottom:24px;transition:color .2s}
.back-link:hover{color:#0d9488}
h1{font-size:32px;font-weight:800;color:#0f172a;margin-bottom:8px;letter-spacing:-.5px}
.updated{font-size:13px;color:#94a3b8;margin-bottom:32px}
h2{font-size:18px;font-weight:700;color:#0f172a;margin:28px 0 10px}
p{font-size:15px;color:#475569;line-height:1.7;margin-bottom:10px}
ul{padding-left:20px;margin-bottom:10px;color:#475569;font-size:15px;line-height:1.7}
li{margin-bottom:6px}
.footer{padding:30px 24px;text-align:center;font-size:13px;color:#94a3b8;background:#0f172a;color:rgba(255,255,255,.5)}
</style>
</head>
<body>
<div class="page-wrap">
    <a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Sign Up</a>
    <h1>Terms of Service</h1>
    <div class="updated">Last updated: September 2026</div>

    <h2>1. Acceptance of Terms</h2>
    <p>By creating an account and using AdamaRent, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the platform.</p>

    <h2>2. Description of Service</h2>
    <p>AdamaRent is an online marketplace that connects landlords and tenants in Adama City. Landlords may list rental properties, and tenants may search for and contact landlords about available properties.</p>

    <h2>3. Account Responsibilities</h2>
    <ul>
        <li>You must provide accurate and complete information when creating your account.</li>
        <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
        <li>You must promptly update your information to keep it accurate.</li>
        <li>You must not create multiple accounts or use the platform for unlawful purposes.</li>
    </ul>

    <h2>4. Listing Guidelines</h2>
    <p>Landlords agree to provide truthful and accurate descriptions of their properties, including price, condition, and availability. Misleading or fraudulent listings may result in account suspension or removal from the platform.</p>

    <h2>5. Conduct</h2>
    <ul>
        <li>No posting of illegal or prohibited content.</li>
        <li>No harassment, abuse, or discrimination of any kind.</li>
        <li>No attempts to bypass the platform's security or services.</li>
    </ul>

    <h2>6. No Guarantee of Transactions</h2>
    <p>AdamaRent facilitates connections between parties but does not guarantee the completion of any rental agreement. All agreements are made directly between landlords and tenants at their own risk.</p>

    <h2>7. Limitation of Liability</h2>
    <p>AdamaRent is not liable for any direct, indirect, incidental, or consequential damages arising from the use of the platform, including disputes between landlords and tenants.</p>

    <h2>8. Termination</h2>
    <p>We reserve the right to suspend or terminate any account that violates these terms or engages in abusive behavior.</p>

    <h2>9. Changes to These Terms</h2>
    <p>We may update these terms from time to time. Continued use of the platform after changes constitutes acceptance of the revised terms.</p>
</div>
<div class="footer">&copy; <?php echo date("Y"); ?> AdamaRent. All rights reserved.</div>
</body>
</html>