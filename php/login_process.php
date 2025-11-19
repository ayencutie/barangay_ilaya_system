<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['login_error'] = "Account not found.";
        header("Location: ../login.php");
        exit;
    }

    // If user has an unexpired OTP pending, redirect to OTP verify page
    try {
        if (!empty($user['otp_code']) && !empty($user['otp_expires'])) {
            $now = new DateTime();
            $exp = new DateTime($user['otp_expires']);
            if ($exp > $now) {
                // redirect user to OTP verification if an OTP is pending
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

    if (!$isValid) {
        // Ensure required columns exist (helps when migrations were not run)
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

        // Increment failed_attempts
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

        // If attempts reached 3, generate OTP, store and send
        if ($attempts >= 3) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = (new DateTime())->add(new DateInterval('PT15M'))->format('Y-m-d H:i:s');
            try {
                $up = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ?, failed_attempts = 0 WHERE email = ?");
                $up->execute([$otp, $expires, $email]);
            } catch (Exception $e) {
                // ignore DB save errors
            }

            // Validate email format and check MX record before attempting mail
            $mailSent = false;
            $mailError = '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mailError = 'Invalid email format.';
            } else {
                $domain = substr(strrchr($email, "@"), 1);
                if (!($domain && (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')))) {
                    $mailError = 'No MX / A DNS records for domain.';
                }
            }

            // If SMTP config exists and is enabled, attempt PHPMailer SMTP send (recommended for Gmail)
            // Prefer environment vars via get_smtp_config.php, fall back to php/smtp_config.php
            $smtp = [];
            $getCfgPath = __DIR__ . '/get_smtp_config.php';
            if (file_exists($getCfgPath)) {
                $smtp = @include $getCfgPath;
            } else {
                $smtpConfigPath = __DIR__ . '/smtp_config.php';
                if (file_exists($smtpConfigPath)) {
                    $smtp = @include $smtpConfigPath;
                }
            }

            if (is_array($smtp) && !empty($smtp['enabled'])) {
                    // attempt to send via PHPMailer (requires composer install: phpmailer/phpmailer)
                    try {
                        $vendor = __DIR__ . '/../vendor/autoload.php';
                        if (file_exists($vendor)) {
                            require_once $vendor;
                            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
                            $mailer->isSMTP();
                            $mailer->Host = $smtp['host'];
                            $mailer->SMTPAuth = true;
                            $mailer->Username = $smtp['username'];
                            $mailer->Password = $smtp['password'];
                            $mailer->SMTPSecure = $smtp['secure'] ?? 'tls';
                            $mailer->Port = $smtp['port'] ?? 587;
                            $mailer->SMTPAutoTLS = $smtp['smtp_auto_tls'] ?? true;
                            $mailer->setFrom($smtp['from_email'] ?? $smtp['username'], $smtp['from_name'] ?? 'No Reply');
                            $mailer->addAddress($email);
                            $mailer->Subject = "Your OTP for Barangay Ilaya";
                            $mailer->Body = "Your one-time code is: $otp\nIt expires in 15 minutes.";
                            $mailer->AltBody = "Your one-time code is: $otp\nIt expires in 15 minutes.";
                            $mailer->send();
                            $mailSent = true;
                        } else {
                            $mailError = 'PHPMailer not installed. Run: composer require phpmailer/phpmailer';
                        }
                    } catch (Exception $e) {
                        $mailError = $e->getMessage();
                        $mailSent = false;
                    }
            }

            // If SMTP not used or failed, fall back to PHP mail()
            if (!$mailSent && empty($mailError)) {
                $subject = "Your OTP for Barangay Ilaya";
                $message = "Your one-time code is: $otp\nIt expires in 15 minutes.";
                $headers = "From: no-reply@localhost" . "\r\n" .
                           "Reply-To: no-reply@localhost" . "\r\n" .
                           "X-Mailer: PHP/" . phpversion();
                $mailSent = @mail($email, $subject, $message, $headers);
                if (!$mailSent) $mailError = 'mail() failed or blocked on server.';
            }

            // If mail failed, store otp_for_test and flag so OTP page can show guidance
            if (!$mailSent) {
                $_SESSION['otp_for_test'] = $otp; // local testing fallback
                $_SESSION['otp_mail_failed'] = true;
                if ($mailError) $_SESSION['otp_mail_error'] = $mailError;
            } else {
                unset($_SESSION['otp_mail_failed']);
                unset($_SESSION['otp_mail_error']);
                unset($_SESSION['otp_for_test']);
            }

            // Redirect to OTP verification page
            header("Location: ../php/otp_verify.php?email=" . urlencode($email));
            exit;
        }

        // Build error message but don't show "Attempts: 0"
        $err = "Wrong password.";
        if ($attempts > 0) $err .= " Attempts: " . $attempts;
        $_SESSION['login_error'] = $err;
        header("Location: ../login.php");
        exit;
    }

    // ===========================================
    // ✅ CORRECT SESSION VARIABLES
    // ===========================================
    $_SESSION['patient_id'] = $user['patient_id'];  // REQUIRED by patient pages
    $_SESSION['user_id']    = $user['patient_id'];  // compatibility with admin auth
    // Keep both keys for compatibility across the app
    $_SESSION['role']       = $user['user_role'];
    $_SESSION['user_role']  = $user['user_role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name']  = $user['last_name'];

    // ===========================================
    // Redirect Based on Role
    // ===========================================
    if ($user['user_role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    }

    header("Location: ../index.html");
    exit;
}
?>
