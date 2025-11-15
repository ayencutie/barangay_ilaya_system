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

if(!$id){
    echo json_encode(["status"=>"error","message"=>"Invalid request."]);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE appointments SET status='Cancelled' WHERE id=:id AND patient_id=:pid");
    $stmt->execute([
        ':id'=>$id,
        ':pid'=>$_SESSION['patient_id']
    ]);

    echo json_encode(["status"=>"success"]);
} catch(PDOException $e){
    echo json_encode(["status"=>"error","message"=>"Database error: ".$e->getMessage()]);
}
?>
