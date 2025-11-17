<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$appointment_id = $data['id'] ?? null;

if (!$appointment_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing appointment ID']);
    exit;
}

$stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);

echo json_encode(['status' => 'success']);
