<?php
session_start();
require 'db.php';
require '../vendor/autoload.php';
$smtpConfig = require 'smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ******************** MASK EMAIL FUNCTION (Para sa masked email display) ********************
function maskEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return $email; }
    list($user, $domain) = explode('@', $email);
    $user_len = strlen($user);
    $start_chars_count = 2; 
    $end_chars_count = 2;   

    if ($user_len > ($start_chars_count + $end_chars_count)) {
        $start = substr($user, 0, $start_chars_count);
        $end = substr($user, -$end_chars_count);
        $mask_length = $user_len - $start_chars_count - $end_chars_count;
        $masked_user = $start . str_repeat('*', $mask_length) . $end;
    } elseif ($user_len > 0) {
        $masked_user = $user[0] . str_repeat('*', $user_len - 1);
    } else {
        $masked_user = '';
    }
    return $masked_user . '@' . $domain;
}
// *****************************************************************************************

function sendOtpEmail($email, $otp, $smtpConfig) {
    // [Ito ang inyong PHPMailer code]
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

    // ******************** CRITICAL FIX: Select needed display fields ********************
    $stmt = $pdo->prepare("SELECT patient_id, first_name, middle_initial, last_name, profile_pic, gender FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Default profile picture paths (ito ang kukunin ng otp_verify.php)
    // Dapat nasa folder na 'uploads' ang mga default files na ito
    $default_male = 'uploads/default_male.svg';
    $default_female = 'uploads/default_female.svg';
    $default_pic = 'uploads/default_profile.png';
    // **********************************************************************************

    if ($user) {
        $otp = random_int(100000, 999999); 
        $expiry = date("Y-m-d H:i:s", time() + 600); 

        $update = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE patient_id = ?");
        $update->execute([$otp, $expiry, $user['patient_id']]);

        sendOtpEmail($email, $otp, $smtpConfig);
        
        // 1. Prepare Full Name
        $mi = !empty($user['middle_initial']) ? " {$user['middle_initial']} " : " ";
        $full_name = trim("{$user['first_name']}{$mi}{$user['last_name']}");
        
        // 2. Determine Profile Picture Path
        $profile_pic = $user['profile_pic'];
        if (empty($profile_pic) || in_array($profile_pic, ['uploads/default_profile.png', 'uploads/default_male.svg', 'uploads/default_female.svg'])) {
            $gender = strtolower($user['gender'] ?? '');
            if ($gender === 'male' || $gender === 'm') {
                $profile_pic = $default_male;
            } elseif ($gender === 'female' || $gender === 'f') {
                $profile_pic = $default_female;
            } else {
                $profile_pic = $default_pic;
            }
        } else {
             // Kung may profile pic, i-append ang version parameter
             $profile_pic = $profile_pic . '?v=' . time(); 
        }
        
        // 3. Store display info in session (ITO ANG MAGPAPALABAS SA PICTURE)
        $_SESSION['reset_user_name'] = $full_name;
        $_SESSION['reset_masked_email'] = maskEmail($email);
        $_SESSION['reset_profile_pic'] = $profile_pic; // Path: uploads/filename.png?v=time
        $_SESSION['reset_patient_id'] = $user['patient_id']; 
        
    } else {
        // If email not found: Set generic info for security 
        $_SESSION['reset_user_name'] = 'User'; 
        $_SESSION['reset_masked_email'] = maskEmail($email);
        $_SESSION['reset_profile_pic'] = $default_pic;
        $_SESSION['reset_patient_id'] = null; 
    }

    $_SESSION['reset_email'] = $email;
    // ***********************************************************************

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
<link rel="stylesheet" href="../css/signup.css"> 
<style>
/* --- Custom Overrides for Perfect Alignment --- */
.container {
    max-width: 450px; 
    padding: 40px 45px; 
    width: 90%; 
    text-align: center; 
}
.forgot-password-form {
    display: flex;
    flex-direction: column;
    align-items: center; 
    gap: 15px;
    width: 100%; 
}
.field.full {
    width: 100%;
}
.forgot-password-form button {
    margin: 0 auto; 
    width: 100%; 
    max-width: 357px; 
}
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
    <p><a href="../landing_page.php">Return to Log In</a></p>

</div>
</body>
</html>