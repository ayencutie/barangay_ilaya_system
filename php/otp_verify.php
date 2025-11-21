<?php
session_start();
require 'db.php';

// Retrieve the email stored during the forgot password request
$email = $_SESSION['reset_email'] ?? '';

// ******************** CHECK: Redirect if email is missing ********************
if (empty($email)) {
    $_SESSION['error_message'] = "Your session has expired or the email is missing. Please start over.";
    header("Location: forgot_password.php");
    exit();
}

// Retrieve display variables (set in forgot_password.php)
$user_name = $_SESSION['reset_user_name'] ?? 'User';
$masked_email = $_SESSION['reset_masked_email'] ?? 'your email';
// CRITICAL: Idagdag ang '../' sa path para mag-work ang image mula sa php/ folder
// Halimbawa: uploads/filename.png?v=time  -->  ../uploads/filename.png?v=time
$raw_pic_url = $_SESSION['reset_profile_pic'] ?? 'uploads/default_profile.png'; 
$profile_pic_url = str_contains($raw_pic_url, '../') ? $raw_pic_url : '../' . $raw_pic_url;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    
    $stmt = $pdo->prepare("SELECT patient_id, otp_code, otp_expires FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]); 
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
        $_SESSION['reset_patient_id'] = $user['patient_id']; 
        
        // Clear all session variables related to OTP reset for security
        unset($_SESSION['reset_email']); 
        unset($_SESSION['reset_user_name']); 
        unset($_SESSION['reset_masked_email']); 
        unset($_SESSION['reset_profile_pic']); 
        
        $clear_otp = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires = NULL WHERE patient_id = ?");
        $clear_otp->execute([$user['patient_id']]);
        
        header("Location: reset_password.php");
        exit();
    }
    
    // If validation fails, redirect back to show error message 
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
#otp-container {
    max-width: 450px !important; 
    width: 90% !important; 
    padding: 30px 45px !important; 
}
.forgot-password-form {
    display: flex;
    flex-direction: column;
    align-items: center; 
    gap: 25px; 
    width: 100%; 
}
.field.full {
    max-width: 350px !important; 
    width: 100% !important; 
}
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
.forgot-password-form button {
    margin: 0 auto; 
    width: 100%; 
    max-width: 350px; 
}

/* ****************** STYLES FOR PROFILE INFO ****************** */
.profile-info-box {
    display: flex;
    align-items: center; 
    justify-content: center; 
    gap: 15px;
    margin-bottom: 25px;
    padding: 10px 0;
    width: 100%;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}
.profile-info-box img {
    width: 60px; 
    height: 60px;
    border-radius: 50%; 
    object-fit: cover;
    border: 2px solid #ccc;
    flex-shrink: 0; 
}
.profile-text {
    text-align: left; 
}
.profile-text p {
    margin: 0; 
    line-height: 1.4;
    font-size: 14px;
    color: #444;
}
.profile-text .masked-email {
    font-weight: bold;
    color: #2e7d32; 
    font-size: 15px; 
}
.profile-text .user-name {
    font-size: 16px;
    font-weight: bold;
    color: #2e7d32; 
}
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
    
    <?php if (!empty($email)): ?>
        <div class="profile-info-box">
            <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" alt="Profile Picture">
            <div class="profile-text">
                <p>Hello, <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>!</p>
                <p>We sent a 6-digit code to:<br>
                <span class="masked-email"><?php echo htmlspecialchars($masked_email); ?></span></p>
            </div>
        </div>
        
    <?php else: ?>
        <p class="alert error" style="margin-bottom: 25px;">Please return to the Forgot Password page to start the process.</p>
    <?php endif; ?>
    <form method="post" action="" class="forgot-password-form">
        
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