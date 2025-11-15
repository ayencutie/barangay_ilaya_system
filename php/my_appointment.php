<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

/* ---------------------------------------
   FIX 1: Use correct session key
   Your login uses account_id
--------------------------------------- */
if (!isset($_SESSION['account_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "You must be logged in."
    ]);
    exit();
}

$patient_id = $_SESSION['account_id']; // Your actual patient ID (0001,0002,...)

/* ---------------------------------------
   FIX 2: Use correct column name
   appointment_id instead of id
--------------------------------------- */
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
