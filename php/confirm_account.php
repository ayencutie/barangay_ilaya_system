<?php
session_start();
require 'db.php'; // Ensure db.php is included

// Step 1: Handle the initial email submission from forgot_password.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: forgot_password.php");
        exit();
    }

    // Fetch the user's details (including name)
    // NOTE: Replace 'name' with your actual column name (e.g., first_name, full_name, etc.)
    $stmt = $pdo->prepare("SELECT email, name FROM users WHERE email = ? LIMIT 1"); 
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Handle case where user is not found (without giving away if the email exists)
        $_SESSION['error_message'] = "We could not find an account with that email address.";
        header("Location: forgot_password.php");
        exit();
    }
    
    // Store user data in session for display
    $_SESSION['confirm_user_name'] = $user['name'];
    $_SESSION['confirm_user_email'] = $user['email'];

} elseif (!isset($_SESSION['confirm_user_email'])) {
    // Redirect if they land here without a valid POST or session
    header("Location: forgot_password.php");
    exit();
}

// Step 2: Handle the confirmation (when the user clicks "Yes")
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Now the user has confirmed. Proceed with OTP generation and sending.
    
    $email_to_reset = $_SESSION['confirm_user_email'];
    
    // --- OTP GENERATION AND STORAGE LOGIC GOES HERE ---
    
    // 1. Generate OTP and Expiry Time
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s'); // 10 minutes expiry

    // 2. Store OTP in database
    $update_stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE email = ?");
    $update_stmt->execute([$otp, $expiry, $email_to_reset]);

    // 3. Send the OTP email (PLACE YOUR EMAIL SENDING CODE HERE)
    // mail($email_to_reset, "Password Reset OTP", "Your code is: $otp", "From: no-reply@yourdomain.com"); 

    // 4. Store email in session for the next step (`otp_verify.php`)
    $_SESSION['reset_email'] = $email_to_reset;
    
    // 5. Clear confirmation session vars
    unset($_SESSION['confirm_user_name']);
    unset($_SESSION['confirm_user_email']);

    // Redirect to the OTP verification page
    header("Location: otp_verify.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Account</title>
<link rel="stylesheet" href="../css/signup.css"> 
<style>
/* Add styling similar to otp_verify.php for container and centering */
#confirm-container { max-width: 450px; width: 90%; padding: 30px 45px; margin: 50px auto; text-align: center; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); background: white; }
.confirm-form button { width: 100%; max-width: 350px; margin: 15px auto; }
</style>
</head>
<body>
<div class="container" id="confirm-container"> 
    <h2>Is this you?</h2>
    
    <div style="text-align: left; background: #f0f4f7; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Account Name:</strong> <?php echo htmlspecialchars($_SESSION['confirm_user_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['confirm_user_email']); ?></p>
    </div>

    <form method="post" action="" class="confirm-form">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit">Yes, Reset My Password</button>
    </form>
    
    <p><a href="forgot_password.php">Not my account / Go back</a></p>

</div>
</body>
</html>