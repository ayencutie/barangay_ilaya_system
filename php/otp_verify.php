<?php
session_start();
require 'db.php';

// OTP verification page: shows form and verifies code
$email = $_GET['email'] ?? $_POST['email'] ?? null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? null;
    $code = trim($_POST['otp'] ?? '');

    if (!$email || !$code) {
        $error = 'Missing email or OTP code.';
    } else {
        $stmt = $pdo->prepare("SELECT otp_code, otp_expires FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $error = 'Account not found.';
        } else {
            $now = new DateTime();
            $exp = $row['otp_expires'] ? new DateTime($row['otp_expires']) : null;
            if (!$row['otp_code'] || !$exp || $exp < $now) {
                $error = 'OTP expired or not found. Please request a new code by trying to log in again.';
            } elseif (hash_equals($row['otp_code'], $code)) {
                // correct: clear otp, reset failed attempts and allow reset password
                $up = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires = NULL, failed_attempts = 0 WHERE email = ?");
                $up->execute([$email]);

                // mark session to allow password reset
                $_SESSION['otp_verified_email'] = $email;
                header('Location: ../reset_password.php');
                exit;
            } else {
                $error = 'Invalid OTP code.';
            }
        }
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Verify OTP</title>
</head>
<body>
  <h1>Enter the OTP sent to your email</h1>
  <?php if ($error): ?>
    <p style="color:red"><?=htmlspecialchars($error)?></p>
  <?php endif; ?>
  <form method="POST">
    <input type="hidden" name="email" value="<?=htmlspecialchars($email)?>">
    <label>OTP code: <input type="text" name="otp" maxlength="6" required></label>
    <button type="submit">Verify</button>
  </form>
  <p>If you didn't receive an email, check spam or contact admin.</p>
  <?php if (!empty($_SESSION['otp_mail_failed'])): ?>
    <p style="color:orange;"><strong>Note:</strong> We couldn't send the OTP by email. <?=htmlspecialchars($_SESSION['otp_mail_error'] ?? '')?> You can use the code shown below (visible locally) to continue.</p>
  <?php endif; ?>
  <?php if (isset($_SESSION['otp_for_test'])): ?>
    <p><small>Test OTP (visible locally): <?=htmlspecialchars($_SESSION['otp_for_test'])?></small></p>
  <?php endif; ?>
</body>
</html>