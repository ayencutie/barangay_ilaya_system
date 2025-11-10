<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['account_id'])){
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if(!$data || !isset($data['appointment_id'], $data['service'], $data['date'], $data['time_slot'])){
    echo json_encode(['status'=>'error','message'=>'Invalid data']);
    exit();
}

$appointment_id = intval($data['appointment_id']);
$service = $data['service'];
$date = $data['date'];
$time_slot = $data['time_slot'];

try {
    $stmt = $pdo->prepare("UPDATE appointments SET service=:service, date=:date, time_slot=:time_slot WHERE appointment_id=:appointment_id AND account_id=:account_id");
    $stmt->execute([
        ':service'=>$service,
        ':date'=>$date,
        ':time_slot'=>$time_slot,
        ':appointment_id'=>$appointment_id,
        ':account_id'=>$_SESSION['account_id']
    ]);

    echo json_encode(['status'=>'success']);
} catch(PDOException $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
