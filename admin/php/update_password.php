<?php
session_start();
require '../db.php';

$adminID = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT password FROM users WHERE patient_id = ?");
$stmt->execute([$adminID]);
$user = $stmt->fetch();

if (!password_verify($_POST['current'], $user['password'])) {
    echo "❌ Current password incorrect";
    exit;
}

$newHash = password_hash($_POST['new'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE patient_id = ?");
$stmt->execute([$newHash, $adminID]);

echo "✅ Password updated successfully";
