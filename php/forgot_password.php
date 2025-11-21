<?php
session_start();
require 'db.php';
require '../vendor/autoload.php';
$smtpConfig = require 'smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOtpEmail($email, $otp, $smtpConfig) {
    $mail = new PHPMailer(true);
    try {
        if ($smtpConfig['enabled']) {
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['username'];
            $mail->Password   = $smtpConfig['password'];
            $mail->SMTPSecure = $smtpConfig['secure'];
            $mail->Port       = $smtpConfig['port'];
            $mail->SMTPAutoTLS = $smtpConfig['smtp_auto_tls'];
        }

        $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Health Center: OTP for Password Reset";
        $mail->Body = "<p>Your OTP for password reset is: <b>$otp</b></p>
                       <p>It expires in 10 minutes.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: forgot_password.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $otp = random_int(100000, 999999); // 6-digit OTP
        $expiry = date("Y-m-d H:i:s", time() + 600); // 10 min

        $update = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE patient_id = ?");
        $update->execute([$otp, $expiry, $user['patient_id']]);

        sendOtpEmail($email, $otp, $smtpConfig);
    }

    $_SESSION['reset_email'] = $email;  // store email for OTP page

    // Redirect to OTP input page
    header("Location: otp_verify.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link rel="stylesheet" href="../css/signup.css"> <style>
/* --- Custom Overrides for Perfect Alignment ---
    These styles override the signup.css defaults to create a compact, 
    perfectly centered form.
*/

/* 1. Override the Container Width for a compact form */
.container {
    max-width: 450px; 
    padding: 40px 45px; 
    width: 90%; 
    /* The 'text-align: center;' is key for centering H2 and P tags */
    text-align: center; 
}

/* 2. Styling for the minimal form to ensure vertical stacking and centering */
.forgot-password-form {
    display: flex;
    flex-direction: column;
    /* Center the form elements (field and button) horizontally */
    align-items: center; 
    gap: 15px;
    width: 100%; 
}

/* 3. Ensure the input wrapper itself takes full width of the form (needed for the input to fill the space) */
.field.full {
    width: 100%;
}

/* 4. Ensure the button is centered and takes full available width (up to max) */
.forgot-password-form button {
    margin: 0 auto; /* Override signup.css margin */
    width: 100%; 
    max-width: 357px; 
}

/* Responsive adjustment for the container */
@media (max-width: 500px) {
    .container {
        max-width: 95%;
        padding: 30px 20px;
    }
}
</style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    
    <form method="post" action="" class="forgot-password-form">
        <div class="field full">
            <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="Enter your Email Address" 
                required
            >
        </div>
        
        <button type="submit">Send OTP</button>
    </form>
    
    <?php
    if (isset($_SESSION['error_message'])) {
        echo "<p style='color:red; font-size: 14px; margin-top: 15px;'>{$_SESSION['error_message']}</p>";
        unset($_SESSION['error_message']);
    }
    ?>
    <p><a href="../login.php">Return to Log In</a></p>

</div>
</body>
</html>