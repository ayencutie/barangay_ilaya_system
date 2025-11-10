<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['account_id'])){
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if(!$data || !isset($data['appointment_id'])){
    echo json_encode(['status'=>'error','message'=>'Invalid data']);
    exit();
}

$appointment_id = intval($data['appointment_id']);

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status='Cancelled' WHERE appointment_id=:appointment_id AND account_id=:account_id");
    $stmt->execute([
        ':appointment_id'=>$appointment_id,
        ':account_id'=>$_SESSION['account_id']
    ]);

    if($stmt->rowCount() > 0){
        echo json_encode(['status'=>'success','message'=>'Appointment cancelled successfully.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Appointment not found or already cancelled.']);
    }
} catch(PDOException $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
