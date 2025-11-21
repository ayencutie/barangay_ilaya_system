<?php
session_start();
require 'db.php';

// Define the maximum attempts before lockout starts
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
        header("Location: ../landing_page.php");
        exit;
    }

    // =======================================================
    // 🚨 LOCKOUT CHECK (MAY FIX DITO) 🚨
    // =======================================================
    try {
        // Tiyakin na may lockout_until data ang user
        if (!empty($user['lockout_until'])) {
            $lockout_until = new DateTime($user['lockout_until']);
            $now = new DateTime();

            if ($lockout_until > $now) {
                // User is currently locked out.
                $interval = $now->diff($lockout_until);
                $minutes = $interval->i;
                $seconds = $interval->s;
                
                $_SESSION['login_error'] = "Too many failed attempts. Please wait " . $minutes . " minute(s) and " . $seconds . " second(s) before trying again.";
                header("Location: ../landing_page.php");
                exit;
            } else {
                // 🛑 FIX: LOCKOUT EXPIRED! I-reset ang attempts sa database para makapag-log in na ulit.
                $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE email = ?")
                    ->execute([$email]);
                
                // I-update ang $user array para sa kasalukuyang execution.
                $user['failed_attempts'] = 0;
                $user['lockout_until'] = NULL;
            }
        }
    } catch (Exception $e) {
        // ignore date parsing errors
    }
    // =======================================================
    
    // --- OPTIONAL: Initial Pending OTP Check (Unchanged) ---
    try {
        if (!empty($user['otp_code']) && !empty($user['otp_expires'])) {
            $now = new DateTime();
            $exp = new DateTime($user['otp_expires']);
            if ($exp > $now) {
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

    // Bcrypt verify and SHA1 fallback (unchanged)
    if (substr($dbPassword, 0, 4) === '$2y$') {
        if (password_verify($password, $dbPassword)) {
            $isValid = true;
        }
    }
    elseif (strlen($dbPassword) === 40 && ctype_xdigit($dbPassword)) {
        if (sha1($password) === $dbPassword) {
            $isValid = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->execute([$newHash, $email]);
        }
    }

    // =======================================================
    // 🟢 START SUCCESSFUL LOGIN PATH 🟢
    // =======================================================
    if ($isValid) {
        
        // 1. Clear old login error messages and attempts
        if (isset($_SESSION['login_error'])) {
            unset($_SESSION['login_error']);
        }
        unset($_SESSION['login_attempts']);

        // 2. Clear failed attempts AND lockout time in the database on successful login
        if ((int)($user['failed_attempts'] ?? 0) > 0 || !empty($user['lockout_until'])) {
            $clearAttempts = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE email = ?");
            $clearAttempts->execute([$email]);
        }
        
        // 3. Set successful session variables (unchanged)
        $_SESSION['patient_id'] = $user['patient_id'];
        $_SESSION['user_id']    = $user['patient_id'];
        $_SESSION['role']       = $user['user_role'];
        $_SESSION['user_role']  = $user['user_role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];

        // 4. Redirect Based on Role (unchanged)
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
        // Ensure required columns exist (Modified to include lockout_until)
        try {
            $colCheck = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'failed_attempts'");
            $colCheck->execute();
            $col = $colCheck->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                // Add missing columns
                $pdo->exec("ALTER TABLE users
                    ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0,
                    ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL,
                    ADD COLUMN otp_expires DATETIME DEFAULT NULL,
                    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0");
            }
             // Check and add lockout_until if it doesn't exist
            $lockoutCheck = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'lockout_until'");
            $lockoutCheck->execute();
            if (!$lockoutCheck->fetch(PDO::FETCH_ASSOC)) {
                 $pdo->exec("ALTER TABLE users ADD COLUMN lockout_until DATETIME DEFAULT NULL");
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
            
            // =======================================================
            // 🚨 LOGIC: SET PROGRESSIVE DELAY 🚨
            // =======================================================
            
            // Calculate delay: Exceeded_count 1 (3rd fail) -> 1 minute
            // Exceeded_count 2 (6th fail) -> 3 minutes
            // Exceeded_count 3 (9th fail) -> 9 minutes (3 * 3)
            
            $exceeded_count = floor($attempts / MAX_ATTEMPTS); 
            
            // Use power of 3 for exponential backoff (base 1)
            $delay_minutes = (int)pow(3, $exceeded_count - 1);
            if ($delay_minutes < 1) {
                $delay_minutes = 1; // Minimum 1 minute delay
            }
            
            // Set the new lockout time
            $lockout_time = new DateTime();
            $lockout_time->modify("+$delay_minutes minutes");
            $lockout_timestamp = $lockout_time->format('Y-m-d H:i:s');
            
            // Update the database with the lockout time
            try {
                $up = $pdo->prepare("UPDATE users SET lockout_until = ? WHERE email = ?");
                $up->execute([$lockout_timestamp, $email]);
            } catch (Exception $e) {
                // ignore DB save errors
            }

            $_SESSION['login_error'] = "Too many failed attempts. You are locked out for the next $delay_minutes minute(s).";
            
            // Redirect pabalik sa login form para ipakita ang lockout message
            header("Location: ../landing_page.php");
            exit;

        } else {
            // B. Default error message for attempts 1 and 2
            $remaining = MAX_ATTEMPTS - $attempts;
            $_SESSION['login_error'] = "Wrong password. You have **$remaining attempt(s)** remaining before a lockout starts.";
            
            // Redirect pabalik sa login form para ipakita ang remaining attempts
            header("Location: ../landing_page.php"); 
            exit;
        }
    }
}
?>