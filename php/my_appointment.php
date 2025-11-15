<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "You must be logged in."
    ]);
    exit();
}

$patient_id = $_SESSION['patient_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            appointment_id,
            service,
            date,
            time_slot,
            status
        FROM appointments
        WHERE patient_id = :patient_id
        ORDER BY date DESC
    ");
    $stmt->execute([':patient_id' => $patient_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add 'id' key for JS compatibility
    foreach ($appointments as &$appt) {
        $appt['id'] = $appt['appointment_id'];
    }

    echo json_encode([
        "status" => "success",
        "appointments" => $appointments
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
