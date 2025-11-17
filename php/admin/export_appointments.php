<?php
// /php/admin/export_appointments.php
session_start();
require_once __DIR__ . '/../php/db.php';

// auth (same admin check)
if (!isset($_SESSION['patient_id'])) { die('Not authenticated'); }
$pid = $_SESSION['patient_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE patient_id = :pid LIMIT 1");
$stmt->execute([':pid'=>$pid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = false;
if ($user) {
    if (isset($user['user_role']) && $user['user_role']==='admin') $isAdmin=true;
    if (!$isAdmin && isset($user['email']) && strtolower($user['email']) === 'admin@healthcenter.com') $isAdmin=true;
    if (!$isAdmin && preg_match('/^ADM/i', $user['patient_id'])) $isAdmin = true;
}
if (!$isAdmin) { die('Not authorized'); }

// build query
$status = $_GET['status'] ?? null;
$q = $_GET['q'] ?? null;
$params = [];
$sql = "SELECT a.appointment_id, a.patient_id, u.first_name, u.last_name, a.service, a.date, a.time_slot, a.status
        FROM appointments a
        LEFT JOIN users u ON a.patient_id = u.patient_id
        WHERE 1=1";

if ($status && $status !== 'All') {
    $sql .= " AND a.status = :status";
    $params[':status'] = $status;
}
if ($q) {
    $sql .= " AND (u.first_name LIKE :q OR u.last_name LIKE :q OR a.service LIKE :q OR a.date LIKE :q OR a.time_slot LIKE :q OR a.patient_id LIKE :q)";
    $params[':q'] = "%$q%";
}

$sql .= " ORDER BY a.date DESC, a.time_slot DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=appointments_export_' . date('Ymd_His') . '.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Appointment ID','Patient ID','First Name','Last Name','Service','Date','Time Slot','Status']);

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $r['appointment_id'],
        $r['patient_id'],
        $r['first_name'],
        $r['last_name'],
        $r['service'],
        $r['date'],
        $r['time_slot'],
        $r['status']
    ]);
}
fclose($out);
exit;
