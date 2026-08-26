<?php 
session_start();
include('db.php');

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];

    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if($res && ($user = mysqli_fetch_assoc($res))){
        if(password_verify($pass, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header("Location: manage_houses.php");
            exit();
        } else {
            echo "<script>alert('Wrong password!');</script>";
        }
    } else {
        echo "<script>alert('Email not found!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Landlord Login</title>
    <style>
        body { 
        font-family: Arial;
         background: #f4f4f4; 
         display: flex; 
         justify-content: center; 
         padding: 50px; 
        }
        .box { 
        background: white; 
        padding: 30px; 
        border-radius: 8px; 
        box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        width: 350px; 
    }
        input { 
            width: 100%; 
            padding: 10px; 
            margin: 10px 0; 
            border: 1px solid #ddd;
             box-sizing: border-box;
             }
        button {
             width: 100%;
              padding: 10px; 
              background: #007bff; 
              color: white; border: none;
               cursor: pointer; 
            }
            h2{
           justify-content: center;  
           margin-Left:60px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Landlord Login</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <p>New? <a href="register.php">Create account</a></p>
    </div>
</body>
</html>