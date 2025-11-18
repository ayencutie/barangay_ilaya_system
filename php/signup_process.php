<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_initial = trim($_POST['middle_initial']);
    $address = trim($_POST['address']);
    $birthdate = $_POST['birthdate'];
    $gender = ($_POST['gender'] === 'Custom' && !empty($_POST['customGender'])) 
        ? trim($_POST['customGender']) 
        : $_POST['gender'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Capitalize names automatically ---
    $first_name = ucfirst(strtolower($first_name));
    $last_name = ucwords(strtolower($last_name)); // capitalize all words, e.g., "De la Cruz"
    $middle_initial = strtoupper($middle_initial);

    // Validate password match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Validate phone format
    if (!preg_match('/^09\d{9}$/', $phone)) {
        echo "<script>alert('Invalid phone number! Must start with 09 and be 11 digits.'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Check for duplicate email
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<script>alert('Email already registered!'); window.history.back();</script>";
            exit();
        }

        // Check for duplicate phone
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo "<script>alert('Phone number already registered!'); window.history.back();</script>";
            exit();
        }

        // Check for duplicate full name
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE first_name=? AND middle_initial=? AND last_name=?");
        $stmt->execute([$first_name, $middle_initial, $last_name]);
        if ($stmt->fetch()) {
            echo "<script>alert('Full name already registered!'); window.history.back();</script>";
            exit();
        }

        // Generate patient_id automatically: PTN-0001, PTN-0002, ...
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(patient_id, 5) AS UNSIGNED)) FROM users");
        $lastPatient = $stmt->fetchColumn();
        $nextNum = $lastPatient ? intval($lastPatient) + 1 : 1;
        $patient_id = 'PTN-' . str_pad($nextNum, 4, "0", STR_PAD_LEFT);

        // Insert new user and set role to 'patient'
        $sql = "INSERT INTO users 
            (patient_id, first_name, middle_initial, last_name, address, birthdate, gender, phone, email, password, user_role)
            VALUES 
            (:patient_id, :first_name, :middle_initial, :last_name, :address, :birthdate, :gender, :phone, :email, :password, :user_role)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':first_name' => $first_name,
            ':middle_initial' => $middle_initial,
            ':last_name' => $last_name,
            ':address' => $address,
            ':birthdate' => $birthdate,
            ':gender' => $gender,
            ':phone' => $phone,
            ':email' => $email,
            ':password' => $hashed_password,
            ':user_role' => 'patient'
        ]);

        // Generate verification OTP and send to the user's email
        try {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = (new DateTime())->add(new DateInterval('PT15M'))->format('Y-m-d H:i:s');
            $up = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ?, email_verified = 0 WHERE email = ?");
            $up->execute([$otp, $expires, $email]);

            // Attempt to send via SMTP/PHPMailer if configured, else fall back to mail()
            $mailSent = false;
            $mailError = '';
            $smtpConfigPath = __DIR__ . '/smtp_config.php';
            if (file_exists($smtpConfigPath)) {
                $smtp = @include $smtpConfigPath;
                if (is_array($smtp) && !empty($smtp['enabled'])) {
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
                            $mailer->Subject = "Verify your email — Barangay Ilaya";
                            $mailer->Body = "Your verification code is: $otp\nIt expires in 15 minutes.";
                            $mailer->AltBody = "Your verification code is: $otp\nIt expires in 15 minutes.";
                            $mailer->send();
                            $mailSent = true;
                        } else {
                            $mailError = 'PHPMailer not installed.';
                        }
                    } catch (Exception $e) {
                        $mailError = $e->getMessage();
                    }
                }
            }

            if (!$mailSent && empty($mailError)) {
                $subject = "Verify your email — Barangay Ilaya";
                $message = "Your verification code is: $otp\nIt expires in 15 minutes.";
                $headers = "From: no-reply@localhost" . "\r\n" .
                           "Reply-To: no-reply@localhost" . "\r\n" .
                           "X-Mailer: PHP/" . phpversion();
                $mailSent = @mail($email, $subject, $message, $headers);
                if (!$mailSent) $mailError = 'mail() failed.';
            }

            if (!$mailSent) {
                $_SESSION['otp_for_test'] = $otp;
                $_SESSION['otp_mail_failed'] = true;
                if ($mailError) $_SESSION['otp_mail_error'] = $mailError;
            } else {
                unset($_SESSION['otp_mail_failed']);
                unset($_SESSION['otp_mail_error']);
                unset($_SESSION['otp_for_test']);
            }

            // Redirect to OTP verification page after signup
            header("Location: ../php/otp_verify.php?email=" . urlencode($email));
            exit;
        } catch (Exception $e) {
            // If sending OTP fails, still finish signup and prompt user to login
            echo "<script>alert('Account created. Failed to send verification email. Please login and request verification.'); window.location='../login.php';</script>";
            exit;
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
