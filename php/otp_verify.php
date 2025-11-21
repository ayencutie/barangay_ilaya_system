<?php
session_start();
require 'db.php';

// Retrieve the email stored during the forgot password request
$email = $_SESSION['reset_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Note: The email is taken from POST if available, otherwise it relies on the session
    $post_email = trim($_POST['email'] ?? $email);
    $otp = trim($_POST['otp'] ?? '');

    // Re-validate the email if it came from the POST
    if (empty($post_email) || !filter_var($post_email, FILTER_VALIDATE_EMAIL)) {
         $_SESSION['error_message'] = "Email address is missing or invalid.";
         header("Location: otp_verify.php");
         exit();
    }
    
    $stmt = $pdo->prepare("SELECT patient_id, otp_code, otp_expires FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$post_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Error handling checks
    if (!$user) {
        $_SESSION['error_message'] = "An error occurred. Please try resetting your password again.";
        header("Location: forgot_password.php");
        exit();
    } elseif ($user['otp_code'] === null || $user['otp_code'] !== $otp) {
        $_SESSION['error_message'] = "Invalid OTP. Please check the code sent to your email.";
    } elseif (new DateTime() > new DateTime($user['otp_expires'])) {
        $_SESSION['error_message'] = "OTP has expired. Please click 'Resend OTP' to get a new code.";
    } else {
        // Success: OTP is valid and not expired
        $_SESSION['reset_user_id'] = $user['patient_id'];
        unset($_SESSION['reset_email']); 
        
        $clear_otp = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires = NULL WHERE patient_id = ?");
        $clear_otp->execute([$user['patient_id']]);
        
        header("Location: reset_password.php");
        exit();
    }

    // Restore email to session if it was passed via POST for next page load
    $_SESSION['reset_email'] = $post_email;
    
    header("Location: otp_verify.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enter OTP</title>
<link rel="stylesheet" href="../css/signup.css"> 

<style>
/* --- Custom Overrides for OTP Verify Page (Guaranteed Alignment) --- */

/* 1. Make the Container Compact and perfectly Centered */
#otp-container {
    /* Use ID selector for high specificity to override .container from signup.css */
    max-width: 450px !important; /* Force smaller width */
    width: 90% !important; 
    padding: 30px 45px !important; /* Shorter padding for a shorter box */
    /* text-align: center is inherited, ensuring h2, p are centered */
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
    width: 100% !important; /* Ensure it respects the max-width */
}

/* 4. Ensure the input type="text" (OTP) adopts the proper styling */
.forgot-password-form input[type="text"] {
    width: 100%;
    padding: 11px;
    border: 1px solid #b0bec5;
    border-radius: 8px;
    outline: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.forgot-password-form input[type="text"]:focus {
    border-color: #2e7d32;
    box-shadow: 0 0 6px rgba(46, 125, 50, 0.4);
}

/* 5. Ensure the button remains centered and sized correctly */
.forgot-password-form button {
    margin: 0 auto; /* Override signup.css margin */
    width: 100%; 
    max-width: 350px; 
}

/* Responsive adjustment for the container */
@media (max-width: 500px) {
    #otp-container {
        max-width: 95% !important;
        padding: 30px 20px !important;
    }
}
</style>

</head>
<body>
<div class="container" id="otp-container"> 
    <h2>Verify OTP</h2>
    
    <form method="post" action="" class="forgot-password-form">
        <div class="field full">
            <input 
                type="email" 
                name="email" 
                placeholder="Your Email" 
                value="<?php echo htmlspecialchars($email); ?>" 
                readonly 
                required
            >
        </div>
        
        <div class="field full">
            <input 
                type="text" 
                name="otp" 
                placeholder="Enter 6-digit OTP" 
                maxlength="6" 
                required
            >
        </div>

        <button type="submit">Verify OTP</button>
    </form>
    
    <?php
    if (isset($_SESSION['error_message'])) {
        echo "<p style='color:red; font-size: 14px; margin-top: 15px;'>{$_SESSION['error_message']}</p>";
        unset($_SESSION['error_message']);
    }
    ?>
    <p><a href="forgot_password.php">Resend OTP</a></p>

</div>
</body>
</html>