<?php
header('Content-Type: application/json');
session_start();
require __DIR__ . '/../../php/db.php';   // ← FIXED PATH

// Admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

// Read JSON input
$input  = json_decode(file_get_contents("php://input"), true);
$id     = $input['id'] ?? null;
$status = $input['status'] ?? null;

if (!$id || !$status) {
    echo json_encode(['status'=>'error','message'=>'Missing fields']);
    exit;
}

try {

    // 1. Update status
    $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);

    // 2. Get appointment info + patient info
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
            CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) AS full_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.patient_id
        WHERE a.appointment_id = :id
        LIMIT 1
    ");
    $stmt2->execute([':id' => $id]);
    $appt = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        echo json_encode(['status'=>'error','message'=>'Appointment not found']);
        exit;
    }

    // 3. Create folder + record file IF COMPLETED
    if (strpos(strtolower(trim($status)), 'complet') !== false) {

        // Clean file-safe name
        $nameClean = preg_replace('/[^A-Za-z0-9_\- ]/', '', $appt['full_name']);
        $folderName = $appt['patient_id'] . "_" . str_replace(" ", "_", trim($nameClean));

        // ROOT: barangay_ilaya_system/patient_records
        $baseDir = dirname(__DIR__, 2) . '/patient_records';

        // Create base folder if missing
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        // Patient folder
        $patientDir = $baseDir . '/' . $folderName;
        if (!is_dir($patientDir)) {
            mkdir($patientDir, 0777, true);
        }

        // Text file path
        $txtPath = $patientDir . "/appointment_" . $appt['appointment_id'] . ".txt";

        // File contents
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

    echo json_encode(['status'=>'success']);
    exit;

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
?>
