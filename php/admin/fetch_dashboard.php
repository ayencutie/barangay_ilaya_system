<?php
// php/admin/fetch_dashboard.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../db.php';

// simple auth check
if (!isset($_SESSION['patient_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

try {
    // 1) total patients
    $hasUserRole = false;
    $colCheck = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'user_role'");
    $colCheck->execute();
    if ($colCheck->fetch()) $hasUserRole = true;

    if ($hasUserRole) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(user_role,'patient') != 'admin'");
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    }
    $totalPatients = (int)$stmt->fetchColumn();

    // 2) today's appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE date = CURDATE()");
    $stmt->execute();
    $todayAppointments = (int)$stmt->fetchColumn();

    // 3) completed appointments (all time)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'");
    $stmt->execute();
    $completedAppointments = (int)$stmt->fetchColumn();

    // 4) pending appointments list (join users for name)
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.patient_id, a.service, a.time_slot, a.date,
               COALESCE(CONCAT(u.first_name, ' ', u.last_name), a.patient_id) AS patient_name
        FROM appointments a
        LEFT JOIN users u ON u.patient_id = a.patient_id
        WHERE a.status = 'Pending'
        ORDER BY a.date ASC, a.time_slot ASC
        LIMIT 50
    ");
    $stmt->execute();
    $pendingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) upcoming appointments (next 7 days)
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.patient_id, a.service, a.time_slot, a.date,
               COALESCE(CONCAT(u.first_name, ' ', u.last_name), a.patient_id) AS patient_name
        FROM appointments a
        LEFT JOIN users u ON u.patient_id = a.patient_id
        WHERE a.date >= CURDATE() AND a.status IN ('Approved','Pending')
        ORDER BY a.date ASC, a.time_slot ASC
        LIMIT 10
    ");
    $stmt->execute();
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'totalPatients' => $totalPatients,
        'todayAppointments' => $todayAppointments,
        'completedAppointments' => $completedAppointments,
        'pendingAppointments' => $pendingAppointments,
        'upcomingAppointments' => $upcoming
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => $e->getMessage()]);
}
