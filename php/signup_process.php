<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db.php';
session_start(); // Ensures you can use $_SESSION for the success message

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

        // 🟢 CHANGE 1: Modified INSERT Query 🟢
        // Set email_verified = 1 and remove otp_code/otp_expires from the INSERT
        $sql = "INSERT INTO users 
            (patient_id, first_name, middle_initial, last_name, address, birthdate, gender, phone, email, password, user_role, email_verified)
            VALUES 
            (:patient_id, :first_name, :middle_initial, :last_name, :address, :birthdate, :gender, :phone, :email, :password, :user_role, 1)"; // <-- 1 means verified
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

        // 🟢 CHANGE 2 & 3: Removed OTP Block and Added Simple Redirect 🟢
        // This replaces the entire 'try...catch' block that handled OTP generation, DB update, email sending, and redirect to otp_verify.php.

        // Account successfully created. Redirect user to the login page.
        $_SESSION['signup_success'] = "Registration successful! You may now log in.";
        header("Location: ../index.html");
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>