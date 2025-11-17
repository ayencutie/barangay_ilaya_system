<?php
// admin/fetch_appointments.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php';

// AUTH: require standardized session keys
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

try {
    $stmt = $pdo->query("
      SELECT a.appointment_id, a.patient_id, a.service, a.date, a.time_slot, a.status,
             u.first_name, u.last_name
      FROM appointments a
      LEFT JOIN users u ON a.patient_id = u.patient_id
      ORDER BY a.date DESC, a.time_slot DESC
    ");

    $list = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $list[] = [
            'appointment_id' => $r['appointment_id'],
            'patient_id'     => $r['patient_id'],
            'patient_name'   => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
            'service'        => $r['service'],
            'date'           => $r['date'],
            'time_slot'      => $r['time_slot'],
            'status'         => $r['status']
        ];
    }

    echo json_encode(['status' => 'success', 'appointments' => $list]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
