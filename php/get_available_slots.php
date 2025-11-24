<?php
session_start();
require 'db.php'; // Your database connection
header('Content-Type: application/json');

// 1. Get user input (Date and Service)
$date = $_GET['date'] ?? null;
$service = $_GET['service'] ?? null;
$patient_id = $_SESSION['patient_id'] ?? null;

if (!$date || !$service) {
    echo json_encode(["status" => "error", "message" => "Date and service are required."]);
    exit();
}

// 2. Query the database for conflicting appointments (Taken Slots)
// We check for conflicts based on the same strict rules we implemented in edit_appointment.php:
// A slot is TAKEN if:
// a) Another patient (patient_id != :pid) has ANY appointment at that date/time. (Check 2)
// b) The current patient (patient_id == :pid) has ANY other appointment at that date/time. (Check 1)
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT time_slot
        FROM appointments 
        WHERE date = :date
          AND status IN ('Pending', 'Approved') 
          AND (
            patient_id != :pid OR (patient_id = :pid AND appointment_id != :exclude_id)
          )
    ");
    
    // For a *new* booking, there is no existing appointment to exclude, so we use a dummy value.
    // However, if we simplify to check ALL conflicting appointments (including self), we can rely on the JS filter.
    
    // Let's simplify and just find ALL time slots taken by ANYONE for the selected date.
    $stmt = $pdo->prepare("
        SELECT DISTINCT time_slot
        FROM appointments 
        WHERE date = :date
          AND status IN ('Pending', 'Approved') 
    ");
    $stmt->execute([':date' => $date]);

    $taken_slots = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    echo json_encode(["status" => "success", "taken_slots" => $taken_slots]);

} catch(PDOException $e){
    error_log("Slot fetch error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Database error checking slots."]);
}
?>