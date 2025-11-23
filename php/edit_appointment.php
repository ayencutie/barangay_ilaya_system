<?php
session_start();
require 'db.php'; // Siguraduhin na tama ang path ng db.php mo
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(["status"=>"error","message"=>"You must be logged in."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$appointment_id = $input['id'] ?? null;
$service = trim($input['service'] ?? '');
$date = $input['date'] ?? '';
$time_slot = trim($input['time_slot'] ?? '');

if(!$appointment_id || !$service || !$date || !$time_slot){
    echo json_encode(["status"=>"error","message"=>"All fields are required."]);
    exit();
}

try {
    // Update ang appointment details at ibalik sa 'Pending' ang status
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET service=:service, date=:date, time_slot=:time_slot, status='Pending'
        WHERE appointment_id=:appointment_id AND patient_id=:pid AND status!='Cancelled'
    ");
    
    $stmt->execute([
        ':service'=>$service,
        ':date'=>$date,
        ':time_slot'=>$time_slot,
        ':appointment_id'=>$appointment_id,
        ':pid'=>$_SESSION['patient_id']
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status"=>"success", "message"=>"Updated successfully"]);
    } else {
        // Success pa rin ang return kahit walang nabago, para hindi mag-error sa frontend
        echo json_encode(["status"=>"success", "message"=>"No changes made"]);
    }

} catch(PDOException $e){
    echo json_encode(["status"=>"error","message"=>"Database error: ".$e->getMessage()]);
}
?>