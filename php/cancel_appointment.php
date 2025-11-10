<?php
session_start();
require 'db.php'; // must define $pdo as PDO instance
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// Read JSON from request
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No appointment ID provided']);
    exit();
}

$appointment_id = $data['id'];

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status='Cancelled' WHERE id = :id");
    $stmt->execute([':id' => $appointment_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Appointment cancelled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Appointment not found or already cancelled.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
