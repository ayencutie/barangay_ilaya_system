<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode([]);
    exit();
}

$patient_id = $_SESSION['patient_id'];

$stmt = $pdo->prepare("
    SELECT service, date, time_slot, status 
    FROM appointments 
    WHERE patient_id = :patient_id
    ORDER BY date DESC
");
$stmt->execute([':patient_id' => $patient_id]);

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($appointments);
?>
