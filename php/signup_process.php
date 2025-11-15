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

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    if (!preg_match('/^09\d{9}$/', $phone)) {
        echo "<script>alert('Invalid phone number! Must start with 09 and be 11 digits.'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // check email
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { 
            echo "<script>alert('Email already registered!'); window.history.back();</script>"; exit(); 
        }

        // check phone
        $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) { 
            echo "<script>alert('Phone number already registered!'); window.history.back();</script>"; exit(); 
        }

        // generate patient_id
        $stmt = $pdo->query("SELECT patient_id FROM users ORDER BY patient_id DESC LIMIT 1");
        $lastPatient = $stmt->fetchColumn();
        $nextNum = $lastPatient ? intval($lastPatient) + 1 : 1;
        $patient_id = str_pad($nextNum, 4, "0", STR_PAD_LEFT);

        // insert
        $sql = "INSERT INTO users 
                (patient_id, first_name, middle_initial, last_name, address, birthdate, gender, phone, email, password)
                VALUES 
                (:patient_id, :first_name, :middle_initial, :last_name, :address, :birthdate, :gender, :phone, :email, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_id'=>$patient_id,
            ':first_name'=>$first_name,
            ':middle_initial'=>$middle_initial,
            ':last_name'=>$last_name,
            ':address'=>$address,
            ':birthdate'=>$birthdate,
            ':gender'=>$gender,
            ':phone'=>$phone,
            ':email'=>$email,
            ':password'=>$hashed_password
        ]);

        echo "<script>alert('Account created successfully!'); window.location='../login.php';</script>";

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
