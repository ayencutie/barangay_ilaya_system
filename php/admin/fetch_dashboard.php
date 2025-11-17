<?php
// php/admin/fetch_dashboard.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../db.php';

/* ===============================
   ADMIN AUTH CHECK (FIXED)
   =============================== */
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'not_admin']);
    exit;
}

try {

    /* ===============================
       TOTAL PATIENTS
       =============================== */
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM users 
        WHERE COALESCE(user_role,'patient') != 'admin'
    ");
    $totalPatients = (int)$stmt->fetchColumn();

    /* ===============================
       TODAY'S APPOINTMENTS
       =============================== */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE date = CURDATE()
    ");
    $stmt->execute();
    $todayAppointments = (int)$stmt->fetchColumn();

    /* ===============================
       COMPLETED APPOINTMENTS
       =============================== */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE status = 'Completed'
    ");
    $stmt->execute();
    $completedAppointments = (int)$stmt->fetchColumn();

    /* ===============================
       PENDING APPOINTMENTS
       =============================== */
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.patient_id,
            a.service,
            a.time_slot,
            a.date,
            CONCAT(u.first_name, ' ', u.last_name) AS patient_name
        FROM appointments a
        LEFT JOIN users u ON u.patient_id = a.patient_id
        WHERE a.status = 'Pending'
        ORDER BY a.date ASC, a.time_slot ASC
        LIMIT 50
    ");
    $stmt->execute();
    $pendingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ===============================
       UPCOMING APPOINTMENTS (next 7 days)
       =============================== */
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.patient_id,
            a.service,
            a.time_slot,
            a.date,
            CONCAT(u.first_name, ' ', u.last_name) AS patient_name
        FROM appointments a
        LEFT JOIN users u ON u.patient_id = a.patient_id
        WHERE a.date >= CURDATE()
          AND a.status IN ('Approved','Pending')
        ORDER BY a.date ASC, a.time_slot ASC
        LIMIT 10
    ");
    $stmt->execute();
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ===============================
       SEND JSON RESULT
       =============================== */
    echo json_encode([
        'totalPatients'        => $totalPatients,
        'todayAppointments'    => $todayAppointments,
        'completedAppointments'=> $completedAppointments,
        'pendingAppointments'  => $pendingAppointments,
        'upcomingAppointments' => $upcomingAppointments
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'db_error',
        'message' => $e->getMessage()
    ]);
}
?>
