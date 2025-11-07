<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Include database connection
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form inputs
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $address = $_POST['address'];
    $birthdate = $_POST['birthdate'];
    $gender = ($_POST['gender'] === 'Custom' && !empty($_POST['customGender'])) 
        ? $_POST['customGender'] 
        : $_POST['gender'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ✅ Check password match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // ✅ Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
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

        echo "<script>alert('Account created successfully!'); window.location='../login.html';</script>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // duplicate email or account_id
            echo "<script>alert('Email already registered!'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
