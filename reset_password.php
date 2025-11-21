<?php
session_start();
require 'php/db.php';

// Allow access only if OTP was verified in this session
$email = $_SESSION['otp_verified_email'] ?? null;
if (!$email) {
    die('Unauthorized.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['confirm_password'] ?? '';
    if (!$pw || !$pw2) $error = 'Fill both password fields.';
    elseif ($pw !== $pw2) $error = 'Passwords do not match.';
    else {
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL, failed_attempts = 0 WHERE email = ?");
        $stmt->execute([$hash, $email]);
        // clear otp_verified flag
        unset($_SESSION['otp_verified_email']);
        // redirect to login with success
        header('Location: login.php?reset=1');
        exit;
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Reset password</title></head>
<body>
  <h1>Reset password for <?=htmlspecialchars($email)?></h1>
  <?php if ($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
  <form method="POST">
    <label>New password: <input type="password" name="password" required></label><br>
    <label>Confirm: <input type="password" name="confirm_password" required></label><br>
    <button type="submit">Set new password</button>
  </form>
</body>
</html>