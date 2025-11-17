<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(["status"=>"error","message"=>"You must be logged in."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['id'] ?? null;

if(!$appointment_id){
    echo json_encode(["status"=>"error","message"=>"Invalid request."]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status='Cancelled' 
        WHERE appointment_id=:appointment_id AND patient_id=:pid
    ");
    $stmt->execute([
        ':appointment_id'=>$appointment_id,
        ':pid'=>$_SESSION['patient_id']
    ]);

    echo json_encode(["status"=>"success"]);
} catch(PDOException $e){
    echo json_encode(["status"=>"error","message"=>"Database error: ".$e->getMessage()]);
}
?>
