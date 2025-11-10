<?php
session_start();
require 'db.php'; // make sure this sets $pdo as PDO instance
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = :user_id ORDER BY date DESC, time_slot ASC");
    $stmt->execute([':user_id' => $user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'appointments' => $appointments]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
