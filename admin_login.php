<?php
session_start();
include('db.php');

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // FIX: Look for any admin level (1 or 2)
    $sql = "SELECT * FROM users WHERE email='$email' AND is_admin >= 1 LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if($res && mysqli_num_rows($res) == 1){
        $user = mysqli_fetch_assoc($res);
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            // FIX: Save the actual rank (2) into the session[cite: 1]
            $_SESSION['is_admin'] = (int)$user['is_admin']; 
            header("Location: admin_panel.php");
            exit();
        } else { $err = "Invalid password."; }
    } else { $err = "Access Denied: Admin account not found."; }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Adama Rent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #2c3e50; display: flex; height: 100vh; align-items: center; justify-content: center; margin:0; }
        .box { background: white; padding: 40px; border-radius: 12px; width: 350px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2c3e50; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <i class="fas fa-user-shield" style="font-size: 50px; color: #2c3e50;"></i>
        <h2>Admin Vault</h2>
        <?php if(isset($err)) echo "<p style='color:red;'>$err</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Admin Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Authorized Entry</button>
        </form>
    </div>
</body>
</html>