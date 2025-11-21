<?php
session_start();
require 'db.php';

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (strlen($new_password) < 8) {
        $_SESSION['error_message'] = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL WHERE patient_id = ?");
        $stmt->execute([$hashed, $_SESSION['reset_user_id']]);

        unset($_SESSION['reset_user_id']);
        $_SESSION['success_message'] = "Password successfully updated. You can now login.";
        header("Location: ../login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link rel="stylesheet" href="../css/signup.css"> 

<style>
/* --- Custom Overrides for Reset Password Page (Guaranteed Alignment) --- */

/* 1. Make the Container Compact and perfectly Centered (Short Container) */
#reset-container {
    /* Use ID selector for high specificity to override .container from signup.css */
    max-width: 450px !important; /* Force smaller width */
    width: 90% !important; 
    padding: 40px 45px !important; /* Short padding for a shorter box */
}

/* 2. Styling for the form to ensure perfect vertical stacking and centering */
.forgot-password-form {
    display: flex;
    flex-direction: column;
    /* Center the form elements (fields and button) horizontally */
    align-items: center; 
    /* Increased gap for better visual separation (hindi masyadong dikit) */
    gap: 25px; 
    width: 100%; 
}

/* 3. Control the maximum width of the input fields themselves */
.field.full {
    /* Set a max width for the field wrapper so the inputs are not too long */
    max-width: 350px !important; 
    width: 100% !important; 
}

/* 4. Ensure the button remains centered and sized correctly */
.forgot-password-form button {
    margin: 0 auto; /* Override signup.css margin */
    width: 100%; 
    max-width: 350px; 
}

/* Error Message Style */
#reset-container p {
    color: red; 
    font-size: 14px; 
    margin-top: 15px;
    /* Ensure the error message is centered */
    width: 100%;
    max-width: 350px;
}

/* Responsive adjustment for the container */
@media (max-width: 500px) {
    #reset-container {
        max-width: 95% !important;
        padding: 30px 20px !important;
    }
}
</style>
</head>
<body>
<div class="container" id="reset-container">
    <h2>Reset Password</h2>
    
    <form method="post" action="" class="forgot-password-form">
        <div class="field full">
            <input type="password" name="password" placeholder="New Password (min 8 chars)" required>
        </div>
        
        <div class="field full">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        </div>

        <button type="submit">Update Password</button>
    </form>
    
    <?php
    if (isset($_SESSION['error_message'])) { 
        echo "<p>{$_SESSION['error_message']}</p>"; 
        unset($_SESSION['error_message']); 
    }
    ?>
</div>
</body>
</html>