<?php
header('Content-Type: application/json');
session_start();
require __DIR__ . '/../db.php';

// ================================
// ADMIN AUTH CHECK
// ================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    exit;
}

// ================================
// READ JSON INPUT
// ================================
$input = json_decode(file_get_contents("php://input"), true);
$id     = $input['id'] ?? null;
$status = $input['status'] ?? null;

if (!$id || !$status) {
    echo json_encode(['status' => 'error', 'message' => 'missing fields']);
    exit;
}

try {

    // ============================
    // 1. UPDATE APPOINTMENT STATUS
    // ============================
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = :status
        WHERE appointment_id = :id
    ");
    $stmt->execute([
        ':status' => $status,
        ':id'     => $id
    ]);

    // ============================
    // 2. FETCH FULL DETAILS
    // ============================
    $stmt2 = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.patient_id,
            a.service,
            a.date,
            a.time_slot,
            u.first_name,
            u.middle_initial,
            u.last_name,
            CONCAT(
                u.first_name, 
                ' ',
                u.middle_initial,
                CASE WHEN u.middle_initial != '' AND u.middle_initial IS NOT NULL THEN '. ' ELSE '' END,
                u.last_name
            ) AS full_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.patient_id
        WHERE a.appointment_id = :id
    ");

    $stmt2->execute([':id' => $id]);
    $appt = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        echo json_encode(['status' => 'error', 'message' => 'appointment not found']);
        exit;
    }

    // ============================
    // 3. CREATE RECORD FILE (IF COMPLETED)
    // ============================
    if ($status === 'Completed') {

        // Clean full name
        $cleanName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $appt['full_name']);

        // FINAL FOLDER NAME:
        // EXAMPLE → PTN-0001_Florence_T_Ayen
        $folderName = $appt['patient_id'] . "_" . str_replace(" ", "_", $cleanName);

        // Directory base
        $baseDir = dirname(__DIR__) . '/patient_records';
        if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

        // Create unique patient folder
        $patientDir = $baseDir . '/' . $folderName;
        if (!is_dir($patientDir)) mkdir($patientDir, 0777, true);

        // File path
        $txtPath = $patientDir . "/appointment_" . $appt['appointment_id'] . ".txt";

        // File content
        $content =
"===== COMPLETED APPOINTMENT =====
Appointment ID : {$appt['appointment_id']}
Patient ID     : {$appt['patient_id']}
Patient Name   : {$appt['full_name']}
Service        : {$appt['service']}
Date           : {$appt['date']}
Time           : {$appt['time_slot']}
Status         : Completed
=================================
";

        file_put_contents($txtPath, $content);
    }

    // ============================
    // SUCCESS RESPONSE
    // ============================
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
