<?php
// /php/admin/edit_appointment.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../php/db.php';

// ensure admin
if (!isset($_SESSION['patient_id'])) { echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit; }
$pid = $_SESSION['patient_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE patient_id = :pid LIMIT 1");
$stmt->execute([':pid'=>$pid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = false;
if ($user) {
    if (isset($user['user_role']) && $user['user_role']==='admin') $isAdmin=true;
    if (!$isAdmin && isset($user['email']) && strtolower($user['email']) === 'admin@health.com') $isAdmin=true;
    if (!$isAdmin && preg_match('/^ADM/i', $user['patient_id'])) $isAdmin = true;
}
if (!$isAdmin) { echo json_encode(['status'=>'error','message'=>'Not authorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$service = trim($input['service'] ?? '');
$date = $input['date'] ?? '';
$time_slot = trim($input['time_slot'] ?? '');

if (!$id || !$service || !$date || !$time_slot) {
    echo json_encode(['status'=>'error','message'=>'All fields required']); exit;
}

try {
    $stmt = $pdo->prepare("UPDATE appointments SET service = :service, date = :date, time_slot = :time_slot WHERE appointment_id = :id");
    $stmt->execute([':service'=>$service, ':date'=>$date, ':time_slot'=>$time_slot, ':id'=>$id]);
    echo json_encode(['status'=>'success']);
    exit;
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
