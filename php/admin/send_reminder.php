<?php
// php/admin/send_reminder.php

ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    // --------------------------------------------------------
    // STEP 1: DATABASE CONNECTION (Auto-Detect Path)
    // --------------------------------------------------------
    $possiblePaths = [
        __DIR__ . '/../db.php',
        __DIR__ . '/../../php/db.php',
        __DIR__ . '/../../db.php'
    ];
    
    $pdo = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require $path;
            break;
        }
    }
    
    if (!$pdo) {
        throw new Exception("Database connection file (db.php) not found.");
    }

    // --------------------------------------------------------
    // STEP 2: GET INPUT DATA
    // --------------------------------------------------------
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;

    if (!$appointment_id) {
        throw new Exception('Error: Invalid Appointment ID.');
    }

    // --------------------------------------------------------
    // STEP 3: FETCH APPOINTMENT & PATIENT DETAILS
    // --------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT a.date, a.time_slot, a.service, a.patient_id, u.first_name, u.last_name
        FROM appointments a
        LEFT JOIN users u ON a.patient_id = u.patient_id
        WHERE a.appointment_id = :id
    ");
    $stmt->execute([':id' => $appointment_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        throw new Exception('Appointment not found.');
    }

    // --------------------------------------------------------
    // STEP 4: VALIDATE PATIENT ID (FIXED FOR "PTN-xxxx")
    // --------------------------------------------------------
    // Dito nabago: Tinanggal ang (int) casting para tanggapin ang string na ID
    $patientId = isset($appt['patient_id']) ? $appt['patient_id'] : '';

    // Check kung empty string o null
    if (empty($patientId)) {
        throw new Exception('Error: Appointment has no associated patient.');
    }

    // Double check: Make sure patient exists
    $checkReceiver = $pdo->prepare("SELECT count(*) FROM users WHERE patient_id = ?");
    $checkReceiver->execute([$patientId]);
    if ($checkReceiver->fetchColumn() == 0) {
        throw new Exception("Database Error: Patient ID ($patientId) does not exist in users table.");
    }

    // --------------------------------------------------------
    // STEP 5: VALIDATE ADMIN ID
    // --------------------------------------------------------
    $adminId = '22'; // Default ID (kahit string pwede na)

    // Check kung nag eexist si Admin 22, kung hindi, maghahanap ng ibang admin
    $checkSender = $pdo->prepare("SELECT count(*) FROM users WHERE patient_id = ? AND user_role = 'admin'");
    $checkSender->execute([$adminId]);
    
    if ($checkSender->fetchColumn() == 0) {
        // Fallback: Hanapin ang unang admin sa database
        $findAdmin = $pdo->query("SELECT patient_id FROM users WHERE user_role = 'admin' LIMIT 1");
        $res = $findAdmin->fetch();
        
        // Dito nabago: Tinanggal din ang (int) para safe kung alphanumeric ang ID ng admin
        $adminId = $res ? $res['patient_id'] : $patientId; 
    }

    // --------------------------------------------------------
    // STEP 6: SEND MESSAGE
    // --------------------------------------------------------
    $patientName = trim($appt['first_name'] . ' ' . $appt['last_name']);
    if (empty($patientName)) {
        $patientName = 'Patient';
    }

    $service = $appt['service'] ?? 'appointment';
    $date = isset($appt['date']) ? date('F j, Y', strtotime($appt['date'])) : '';
    $time = $appt['time_slot'] ?? '';

    $messageBody = "Hello $patientName, reminder lang po mula sa Barangay Ilaya Health Center para sa inyong $service appointment sa $date, $time. Please be on time. Salamat!";

    $insertStmt = $pdo->prepare("
        INSERT INTO chat_messages (sender_id, receiver_id, message_body) 
        VALUES (:sender, :receiver, :msg)
    ");

    $insertStmt->execute([
        ':sender' => $adminId,
        ':receiver' => $patientId,
        ':msg' => $messageBody
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Reminder sent successfully']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>