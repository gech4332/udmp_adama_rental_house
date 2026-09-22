<?php include('includes/session_config.php'); session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Policy - AdamaRent</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
    <h1>Privacy Policy</h1>
    <div class="updated">Last updated: September 2026</div>

    <h2>1. Information We Collect</h2>
    <ul>
        <li>Account information: your name, email address, and password (stored securely encrypted).</li>
        <li>Listing information: properties you post, including photos, prices, and descriptions.</li>
        <li>Usage information: interactions with the platform such as property searches and rental requests.</li>
    </ul>

    <h2>2. How We Use Your Information</h2>
    <ul>
        <li>To create and manage your account.</li>
        <li>To display your property listings to potential tenants.</li>
        <li>To facilitate communication between landlords and tenants.</li>
        <li>To send you important account and verification notifications.</li>
        <li>To improve and secure the platform.</li>
    </ul>

    <h2>3. Information Sharing</h2>
    <p>We do not sell your personal information to third parties. Your contact details may be shared with other users solely for the purpose of arranging property rentals through the platform.</p>

    <h2>4. Data Security</h2>
    <p>We use industry-standard security measures, including encrypted password storage, to protect your personal information.</p>

    <h2>5. Your Rights</h2>
    <p>You may update your account information at any time through your profile. You may request deletion of your account and associated data by contacting us.</p>

    <h2>6. Cookies and Tracking</h2>
    <p>We use session-based cookies to keep you signed in and to protect your account. These are essential for the platform to function properly.</p>

    <h2>7. Contact Us</h2>
    <p>If you have any questions about this Privacy Policy, please contact us at getahun930@gmail.com.</p>
</div>
<div class="footer">&copy; <?php echo date("Y"); ?> AdamaRent. All rights reserved.</div>
</body>
</html>