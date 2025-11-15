<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(["status"=>"error","message"=>"You must be logged in."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$service = trim($input['service'] ?? '');
$date = $input['date'] ?? '';
$time_slot = trim($input['time_slot'] ?? '');

if(!$id || !$service || !$date || !$time_slot){
    echo json_encode(["status"=>"error","message"=>"All fields are required."]);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE appointments 
                           SET service=:service, date=:date, time_slot=:time_slot 
                           WHERE id=:id AND patient_id=:pid AND status!='Cancelled'");
    $stmt->execute([
        ':service'=>$service,
        ':date'=>$date,
        ':time_slot'=>$time_slot,
        ':id'=>$id,
        ':pid'=>$_SESSION['patient_id']
    ]);

    echo json_encode(["status"=>"success"]);
} catch(PDOException $e){
    echo json_encode(["status"=>"error","message"=>"Database error: ".$e->getMessage()]);
}
?>
