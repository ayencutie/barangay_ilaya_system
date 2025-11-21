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
</head>
<body>
<h2>Forgot Password</h2>
<form method="post" action="">
    <input type="email" name="email" placeholder="Email Address" required>
    <button type="submit">Send OTP</button>
</form>
<?php
if (isset($_SESSION['error_message'])) {
    echo "<p style='color:red'>{$_SESSION['error_message']}</p>";
    unset($_SESSION['error_message']);
}
?>
<a href="../login.php">Return to Login</a>
</body>
</html>
