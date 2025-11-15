<?php
session_start();
require 'db.php';

if (!isset($_SESSION['patient_id'])) {
    exit('<div class="error-message">Not logged in</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = $_SESSION['patient_id'];
    $oldpwd = trim($_POST['old_password']);
    $newpwd = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if ($newpwd !== $confirm) {
        exit('<div class="error-message">New passwords do not match</div>');
    }

    // Get hashed password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE patient_id = :patient_id");
    $stmt->execute([':patient_id' => $patient_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($oldpwd, $user['password'])) {
        exit('<div class="error-message">Old password is incorrect</div>');
    }

    // Update password
    $hashed = password_hash($newpwd, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE patient_id = :patient_id");
    $stmt->execute([
        ':password' => $hashed,
        ':patient_id' => $patient_id
    ]);

    exit('<div class="success-message">Password updated successfully!</div>');
}
?>
