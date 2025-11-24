<?php
session_start();
// IMPORTANT: Replace 'db.php' with the correct path to your database connection file
require 'db.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in to edit appointments."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$appointment_id = $input['id'] ?? null;
$service = trim($input['service'] ?? '');
$date = $input['date'] ?? '';
$time_slot = trim($input['time_slot'] ?? '');
$patient_id = $_SESSION['patient_id']; // This is the ID of the person making the edit

if (!$appointment_id || !$service || !$date || !$time_slot) {
    echo json_encode(["status" => "error", "message" => "All fields (Appointment ID, Service, Date, Time Slot) are required."]);
    exit();
}

try {
    // =================================================================
    // === CONFLICT CHECK 1: PREVENT SELF-OVERLAP (The current user has ANY appointment at this time) ===
    // This blocks the patient from scheduling two appointments concurrently (e.g., Dental and Family Planning at 1 PM).
    // =================================================================
    $self_check_stmt = $pdo->prepare("
        SELECT appointment_id FROM appointments 
        WHERE patient_id = :pid
          AND date = :date
          AND time_slot = :time_slot
          AND status IN ('Pending', 'Approved') 
          AND appointment_id != :appointment_id
    ");

    $self_check_stmt->execute([
        ':pid' => $patient_id, 
        ':date' => $date,
        ':time_slot' => $time_slot,
        ':appointment_id' => $appointment_id
    ]);
    
    if ($self_check_stmt->fetch()) {
        echo json_encode([
            "status" => "error",
            "message" => "❌ You already have another appointment at this exact time. Please choose a different slot."
        ]);
        exit();
    }
    
    // =================================================================
    // === CONFLICT CHECK 2: PREVENT CROSS-USER OVERBOOKING (Another person has ANY appointment at this time) ===
    // This blocks the edit if ANY other patient has a booking at the same date and time, regardless of service.
    // This ensures Person 1 cannot take Person 2's time slot.
    // =================================================================
    $other_user_check_stmt = $pdo->prepare("
        SELECT appointment_id FROM appointments 
        WHERE date = :date
          AND time_slot = :time_slot
          AND status IN ('Pending', 'Approved') 
          AND patient_id != :pid 
    ");

    $other_user_check_stmt->execute([
        ':date' => $date,
        ':time_slot' => $time_slot,
        ':pid' => $patient_id // Ensures we only check against OTHER patients
    ]);

    if ($other_user_check_stmt->fetch()) {
        echo json_encode([
            "status" => "error",
            "message" => "❌ The time slot is already occupied by another patient. Please choose a different time."
        ]);
        exit();
    }
    // --- END CONFLICT CHECKS ---

    // =================================================================
    // === STEP 3: UPDATE APPOINTMENT DETAILS (If all checks pass) ===
    // =================================================================
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET service=:service, date=:date, time_slot=:time_slot, status='Pending'
        WHERE appointment_id=:appointment_id AND patient_id=:pid AND status!='Cancelled'
    ");
    
    $stmt->execute([
        ':service' => $service,
        ':date' => $date,
        ':time_slot' => $time_slot,
        ':appointment_id' => $appointment_id,
        ':pid' => $patient_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Appointment updated successfully. It has been moved to Pending for re-confirmation."]);
    } else {
        echo json_encode(["status" => "success", "message" => "No changes were detected or the appointment status prevents immediate editing."]);
    }

} catch(PDOException $e){
    // Log error for server debugging
    error_log("Edit Appointment DB Error: " . $e->getMessage()); 
    echo json_encode(["status" => "error", "message" => "A database error occurred during the update."]);
}
?>