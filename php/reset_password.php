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
</head>
<body>
<h2>Reset Password</h2>
<form method="post" action="">
    <input type="password" name="password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Update Password</button>
</form>
<?php
if (isset($_SESSION['error_message'])) { echo "<p style='color:red'>{$_SESSION['error_message']}</p>"; unset($_SESSION['error_message']); }
?>
</body>
</html>
