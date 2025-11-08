<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit('Error: not logged in');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // ✅ Strict validation: must start with 09 and be 11 digits
    if (!preg_match('/^09\d{9}$/', $phone)) {
        exit('<div class="error-message">Invalid phone number! Must start with 09 and be 11 digits.</div>');
    }

    // 🔒 Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        exit('<div class="error-message">Wrong password</div>');
    }

    // 🧩 Optional: check if phone is already used by another account
    $check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone AND id != :id");
    $check->execute([':phone' => $phone, ':id' => $user_id]);
    if ($check->fetch()) {
        exit('<div class="error-message">This phone number is already in use.</div>');
    }

    // ✅ Update phone
    $stmt = $pdo->prepare("UPDATE users SET phone = :phone WHERE id = :id");
    $stmt->execute([':phone' => $phone, ':id' => $user_id]);

    exit('<div class="success-message">Phone number updated successfully!</div>');
}
?>
