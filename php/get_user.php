<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$patient_id = $_SESSION['patient_id'];

$stmt = $pdo->prepare("
    SELECT patient_id, first_name, last_name, address, birthdate, email, phone
    FROM users
    WHERE patient_id = :patient_id
");
$stmt->execute([':patient_id' => $patient_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode($user);
?>
