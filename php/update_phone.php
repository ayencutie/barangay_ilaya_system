<?php
session_start();
require 'db.php';

if (!isset($_SESSION['patient_id'])) {
    exit('<div class="error-message">Not logged in</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = $_SESSION['patient_id'];
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // Phone validation
    if (!preg_match('/^09\d{9}$/', $phone)) {
        exit('<div class="error-message">Invalid phone number! Must start with 09 and be 11 digits.</div>');
    }

    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE patient_id = :patient_id");
    $stmt->execute([':patient_id' => $patient_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        exit('<div class="error-message">Incorrect password</div>');
    }

    // Check if phone exists in another account
    $check = $pdo->prepare("SELECT patient_id FROM users WHERE phone = :phone AND patient_id != :patient_id");
    $check->execute([
        ':phone' => $phone,
        ':patient_id' => $patient_id
    ]);

    if ($check->fetch()) {
        exit('<div class="error-message">Phone number already used by another account</div>');
    }

    // Update phone
    $stmt = $pdo->prepare("UPDATE users SET phone = :phone WHERE patient_id = :patient_id");
    $stmt->execute([
        ':phone' => $phone,
        ':patient_id' => $patient_id
    ]);

    exit('<div class="success-message">Phone number updated successfully!</div>');
}
?>
