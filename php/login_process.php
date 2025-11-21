<?php
session_start();
require 'db.php';

// Define the threshold for suggesting a password reset
const MAX_ATTEMPTS = 3;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // IMPORTANT: Use a generic error here to prevent user enumeration
        $_SESSION['login_error'] = "Invalid email or password."; 
        header("Location: ../login.php");
        exit;
    }

    // --- OPTIONAL: Initial Pending OTP Check ---
    try {
        if (!empty($user['otp_code']) && !empty($user['otp_expires'])) {
            $now = new DateTime();
            $exp = new DateTime($user['otp_expires']);
            if ($exp > $now) {
                // Set the session attempt count to the DB value before redirecting to inform the user
                $_SESSION['login_attempts'] = (int)($user['failed_attempts'] ?? 0);
                header("Location: ../php/otp_verify.php?email=" . urlencode($email));
                exit;
            }
        }
    } catch (Exception $e) {
        // ignore parsing errors and continue
    }

    $dbPassword = $user['password'];
    $isValid = false;

    // Bcrypt verify
    if (substr($dbPassword, 0, 4) === '$2y$') {
        if (password_verify($password, $dbPassword)) {
            $isValid = true;
        }
    }
    // SHA1 fallback
    elseif (strlen($dbPassword) === 40 && ctype_xdigit($dbPassword)) {
        if (sha1($password) === $dbPassword) {
            $isValid = true;

            // Upgrade password to bcrypt
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->execute([$newHash, $email]);
        }
    }

    // =======================================================
    // 🟢 START SUCCESSFUL LOGIN PATH 🟢
    // =======================================================
    if ($isValid) {
        
        // 1. Clear old login error messages from the session
        if (isset($_SESSION['login_error'])) {
            unset($_SESSION['login_error']);
        }
        // Also clear the attempt counter session variable
        unset($_SESSION['login_attempts']);

        // 2. Clear failed attempts in the database on successful login
        if ((int)($user['failed_attempts'] ?? 0) > 0) {
            $clearAttempts = $pdo->prepare("UPDATE users SET failed_attempts = 0 WHERE email = ?");
            $clearAttempts->execute([$email]);
        }
        
        // 3. Set successful session variables
        $_SESSION['patient_id'] = $user['patient_id'];
        $_SESSION['user_id']    = $user['patient_id'];
        $_SESSION['role']       = $user['user_role'];
        $_SESSION['user_role']  = $user['user_role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];

        // 4. Redirect Based on Role
        if ($user['user_role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
            exit;
        }

        header("Location: ../index.html");
        exit;
    }
    // =======================================================
    // 🔴 START FAILED LOGIN PATH 🔴
    // =======================================================
    else { 
        // Ensure required columns exist (unchanged schema check)
        try {
            $colCheck = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'failed_attempts'");
            $colCheck->execute();
            $col = $colCheck->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                // Add missing columns: failed_attempts, otp_code, otp_expires, email_verified
                $pdo->exec("ALTER TABLE users
                    ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0,
                    ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL,
                    ADD COLUMN otp_expires DATETIME DEFAULT NULL,
                    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0");
            }
        } catch (Exception $e) {
            // ignore — we'll still try to continue
        }

        // Increment failed_attempts and fetch current attempts
        try {
            $inc = $pdo->prepare("UPDATE users SET failed_attempts = COALESCE(failed_attempts,0) + 1 WHERE email = ?");
            $inc->execute([$email]);
            $stmt2 = $pdo->prepare("SELECT failed_attempts FROM users WHERE email = ? LIMIT 1");
            $stmt2->execute([$email]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);
            $attempts = isset($row['failed_attempts']) ? (int)$row['failed_attempts'] : 0;
        } catch (Exception $e) {
            $attempts = 0;
        }

        // CRITICAL: Synchronize DB attempt count with session for frontend display
        $_SESSION['login_attempts'] = $attempts; 

        if ($attempts >= MAX_ATTEMPTS) {
            // A. Too many attempts: Set specific suggestion error message
            $_SESSION['login_error'] = "Too many failed attempts. We highly suggest you use the 'Forgot Password' link below.";
            
            // Optional: Reset attempts in the database since we are suggesting a reset path.
            try {
                $up = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires = NULL, failed_attempts = 0 WHERE email = ?");
                $up->execute([$email]);
                // Clear session attempts too if DB is cleared
                unset($_SESSION['login_attempts']); 
            } catch (Exception $e) {
                // ignore DB save errors
            }

        } else {
            // B. Default error message for attempts 1 and 2
            $remaining = MAX_ATTEMPTS - $attempts;
            $_SESSION['login_error'] = "Wrong password. You have $remaining attempt(s) remaining before a reset is suggested.";
        }

        header("Location: ../login.php");
        exit;
    }
}
?>