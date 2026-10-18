<?php
include('includes/session_config.php');
session_start();

// 1. Determine if this was an administrator session
$was_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 1;

// 2. Clear all session variables
$_SESSION = array();

// 3. Kill the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy the session on the server
session_destroy();

// 5. Redirect — administrators to the dedicated admin login, everyone else to Home
if ($was_admin) {
    header("Location: admin_login.php");
    exit();
}
header("Location: Home.php");
exit();
?>