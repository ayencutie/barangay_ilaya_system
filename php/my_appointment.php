<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['account_id'])){
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$account_id = $_SESSION['account_id'];

try {
    $stmt = $pdo->prepare("SELECT appointment_id, service, date, time_slot, status FROM appointments WHERE account_id = :account_id ORDER BY date DESC, time_slot ASC");
    $stmt->execute([':account_id' => $account_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success','appointments'=>$appointments]);
} catch(PDOException $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
