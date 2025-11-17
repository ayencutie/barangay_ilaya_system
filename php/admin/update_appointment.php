<?php
header('Content-Type: application/json');
session_start();
require __DIR__ . '/../db.php';

// ADMIN ONLY
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'forbidden']);
    exit;
}

// GET JSON INPUT 
$input  = json_decode(file_get_contents("php://input"), true);
$id     = $input['id'] ?? null;
$status = $input['status'] ?? null;

if (!$id || !$status) {
    echo json_encode(['status'=>'error','message'=>'missing fields']);
    exit;
}

try {

    // 1. UPDATE STATUS
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = :status
        WHERE appointment_id = :id
    ");
    $stmt->execute([
        ':status' => $status,
        ':id'     => $id
    ]);

    // 2. GET APPOINTMENT + USER INFO (FIXED JOIN)
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
            CONCAT(u.first_name, ' ', u.last_name) AS full_name
        FROM appointments a
        JOIN users u ON a.patient_id = u.patient_id
        WHERE a.appointment_id = :id
    ");
    $stmt2->execute([':id' => $id]);
    $appt = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        echo json_encode(['status'=>'error','message'=>'appointment not found']);
        exit;
    }

    // IF COMPLETED → create folder + file
    if ($status === 'Completed') {

        // folder name: PTN-0001_Florence_T_Ayen
        $nameClean = preg_replace('/[^A-Za-z0-9_\- ]/', '', $appt['full_name']);
        $folderName = $appt['patient_id'] . "_" . str_replace(" ", "_", $nameClean);

        $baseDir = dirname(__DIR__) . '/patient_records';

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        $patientDir = $baseDir . '/' . $folderName;

        if (!is_dir($patientDir)) {
            mkdir($patientDir, 0777, true);
        }

        // file path
        $txtPath = $patientDir . "/appointment_" . $appt['appointment_id'] . ".txt";

        // txt content
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

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
