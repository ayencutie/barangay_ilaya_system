<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start(); // Start session to use $_SESSION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- Collect and sanitize input ---
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_initial = trim($_POST['middle_initial']);
    $address = trim($_POST['address']);
    $birthdate = $_POST['birthdate'] ?? null;
    $gender = ($_POST['gender'] === 'Custom' && !empty($_POST['customGender'])) 
        ? trim($_POST['customGender']) 
        : $_POST['gender'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Capitalize names properly ---
    $first_name = ucfirst(strtolower($first_name));
    $last_name = ucwords(strtolower($last_name));
    $middle_initial = strtoupper($middle_initial);

    // --- Validate birthdate ---
    if (!$birthdate) {
        echo "<script>alert('Please select a complete birthdate.'); window.history.back();</script>";
        exit();
    }

    // --- Password minimum length validation ---
    if (strlen($password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long.'); window.history.back();</script>";
        exit();
    }

    // --- Password match validation ---
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // --- Phone format validation ---
    if (!preg_match('/^09\d{9}$/', $phone)) {
        echo "<script>alert('Invalid phone number! Must start with 09 and be 11 digits.'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // --- Duplicate email check ---
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<script>alert('Email already registered!'); window.history.back();</script>";
            exit();
        }

        // --- Duplicate phone check ---
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo "<script>alert('Phone number already registered!'); window.history.back();</script>";
            exit();
        }

        // --- Duplicate full name check ---
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE first_name=? AND middle_initial=? AND last_name=?");
        $stmt->execute([$first_name, $middle_initial, $last_name]);
        if ($stmt->fetch()) {
            echo "<script>alert('Full name already registered!'); window.history.back();</script>";
            exit();
        }

        // --- Generate patient_id automatically ---
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(patient_id, 5) AS UNSIGNED)) FROM users");
        $lastPatient = $stmt->fetchColumn();
        $nextNum = $lastPatient ? intval($lastPatient) + 1 : 1;
        $patient_id = 'PTN-' . str_pad($nextNum, 4, "0", STR_PAD_LEFT);

        // --- Insert user into DB ---
        $sql = "INSERT INTO users 
            (patient_id, first_name, middle_initial, last_name, address, birthdate, gender, phone, email, password, user_role, email_verified)
            VALUES 
            (:patient_id, :first_name, :middle_initial, :last_name, :address, :birthdate, :gender, :phone, :email, :password, :user_role, 1)";
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

        // --- Automatically log in the user ---
        $_SESSION['patient_id'] = $patient_id;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['user_role'] = 'patient';
        $_SESSION['signup_success'] = "Welcome, $first_name! You are now logged in.";

        // --- Redirect to logged-in landing page ---
        header("Location: ../index.html");
        exit;

    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
}
?>
