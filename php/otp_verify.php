<?php
session_start();
require 'db.php';

$email = $_SESSION['reset_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare("SELECT patient_id, otp_code, otp_expires FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['otp_code'] !== $otp) {
        $_SESSION['error_message'] = "Invalid OTP.";
    } elseif (new DateTime() > new DateTime($user['otp_expires'])) {
        $_SESSION['error_message'] = "OTP has expired.";
    } else {
        $_SESSION['reset_user_id'] = $user['patient_id'];
        unset($_SESSION['reset_email']); // clear email after OTP verified
        header("Location: reset_password.php");
        exit();
    }

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
</head>
<body>
<h2>Enter OTP</h2>
<form method="post" action="">
    <input type="email" name="email" placeholder="Your Email" value="<?php echo htmlspecialchars($email); ?>" required>
    <input type="text" name="otp" placeholder="Enter OTP" required>
    <button type="submit">Verify OTP</button>
</form>
<?php
if (isset($_SESSION['error_message'])) {
    echo "<p style='color:red'>{$_SESSION['error_message']}</p>";
    unset($_SESSION['error_message']);
}
?>
<a href="forgot_password.php">Resend OTP</a>
</body>
</html>
