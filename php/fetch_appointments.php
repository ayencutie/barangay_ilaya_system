<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT service, date, time_slot, status FROM appointments WHERE user_id = :user_id ORDER BY date DESC");
$stmt->execute([':user_id' => $user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($appointments);
?>
