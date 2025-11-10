<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

// ✅ Check login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

// ✅ Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit();
}

// ✅ Get form data
$user_id = $_SESSION['user_id'];
$account_id = $_SESSION['account_id'];
$service = $_POST['service'] ?? '';
$date = $_POST['date'] ?? '';
$time_slot = $_POST['time_slot'] ?? '';

// ✅ Validate
if (!$service || !$date || !$time_slot) {
    echo json_encode(['status'=>'error','message'=>'Please select service, date, and time']);
    exit();
}

// ✅ Insert appointment
try {
    $stmt = $pdo->prepare("
        INSERT INTO appointments (user_id, account_id, service, date, time_slot, status)
        VALUES (:user_id, :account_id, :service, :date, :time_slot, 'Pending')
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':account_id' => $account_id,
        ':service' => $service,
        ':date' => $date,
        ':time_slot' => $time_slot
    ]);
    echo json_encode(['status'=>'success','message'=>'Appointment booked successfully']);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
