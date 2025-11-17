<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php'; // ← FIXED PATH

// admin check
if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not authenticated']); 
    exit;
}

$pid = $_SESSION['patient_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE patient_id = :pid LIMIT 1");
$stmt->execute([':pid'=>$pid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { 
    echo json_encode(['status'=>'error','message'=>'Invalid user']); 
    exit; 
}

$isAdmin = false;

// check admin role
if (isset($user['user_role']) && $user['user_role'] === 'admin') $isAdmin = true;

// default admin email
if (!$isAdmin && strtolower($user['email']) === 'admin@healthcenter.com') $isAdmin = true;

// ADM ID prefix
if (!$isAdmin && preg_match('/^ADM/i', $user['patient_id'])) $isAdmin = true;

if (!$isAdmin) { 
    echo json_encode(['status'=>'error','message'=>'Not authorized']); 
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
            'patient_id' => $r['patient_id'],
            'patient_name' => trim($r['first_name'].' '.$r['last_name']),
            'service' => $r['service'],
            'date' => $r['date'],
            'time_slot' => $r['time_slot'],
            'status' => $r['status']
        ];
    }

    echo json_encode(['status'=>'success','appointments'=>$list]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
?>
