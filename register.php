`<?php 
include('db.php'); 

if(isset($_POST['register'])){
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure hashing

    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if(mysqli_num_rows($check_email) > 0){
        echo "<script>alert('Email already exists!');</script>";
    } else {
        $sql = "INSERT INTO users (full_name, email, password) VALUES ('$name', '$email', '$pass')";
        if(mysqli_query($conn, $sql)){
            echo "<script>alert('Registration successful!'); window.location='login.php';</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Landlord Registration</title>
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
             width: 350px; }
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
        background: #28a745; 
        color: white; 
        border: none;
         cursor: pointer; 
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Landlord Register</h2>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Create Password" required>
            <button type="submit" name="register">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>