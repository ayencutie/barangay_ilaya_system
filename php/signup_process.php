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

    // ✅ Password match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // ✅ Phone number validation (starts with 09, 11 digits)
    if (!preg_match('/^09\d{9}$/', $phone)) {
        echo "<script>alert('Invalid phone number! Must start with 09 and be 11 digits.'); window.history.back();</script>";
        exit();
    }

    // ✅ Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // ✅ Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<script>alert('Email already registered!'); window.history.back();</script>";
            exit();
        }

        // ✅ Check if phone number already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo "<script>alert('Phone number already registered!'); window.history.back();</script>";
            exit();
        }

        // ✅ Generate unique account_id (e.g., 0001, 0002, 0003)
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $account_id = str_pad($count + 1, 4, "0", STR_PAD_LEFT);

        // ✅ Insert user data
        $sql = "INSERT INTO users 
                (account_id, first_name, middle_initial, last_name, address, birthdate, gender, phone, email, password)
                VALUES 
                (:account_id, :first_name, :middle_initial, :last_name, :address, :birthdate, :gender, :phone, :email, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':account_id' => $account_id,
            ':first_name' => $first_name,
            ':middle_initial' => $middle_initial,
            ':last_name' => $last_name,
            ':address' => $address,
            ':birthdate' => $birthdate,
            ':gender' => $gender,
            ':phone' => $phone,
            ':email' => $email,
            ':password' => $hashed_password
        ]);

        echo "<script>alert('Account created successfully!'); window.location='../index.html';</script>";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // duplicate constraint
            echo "<script>alert('Duplicate entry detected!'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
