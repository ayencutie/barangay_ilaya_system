<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Health Center | Login</title>
    <link rel="stylesheet" href="css/login.css" />
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://i.ibb.co/zVbCpfct/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Health Center Logo" />
        </div>

        <h2>Health Center Login</h2>

        <?php
            if (isset($_SESSION['login_error'])) {
                echo "<div class='error-message'>" . htmlspecialchars($_SESSION['login_error']) . "</div>";
                unset($_SESSION['login_error']);
            }
            if (isset($_SESSION['signup_success'])) {
                echo "<div class='success-message'>" . htmlspecialchars($_SESSION['signup_success']) . "</div>";
                unset($_SESSION['signup_success']);
            }
        ?>

        <form action="php/login_process.php" method="post">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />

            <button type="submit" class="button-link">Log In</button>

            <div class="forgot-password-link">
                <a href="php/forgot_password.php">Forgot password?</a>
            </div>
            </form>
        
        <div class="separator-line"></div>
        
        <a href="signup.html" class="create-account-button">Create new account</a>
        
    </div>
</body>
</html>