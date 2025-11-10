<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['id'], $input['service'], $input['date'], $input['time_slot'])){
    echo json_encode(['status'=>'error','message'=>'Invalid data']);
    exit();
}

$id = intval($input['id']);
$service = $input['service'];
$date = $input['date'];
$time_slot = $input['time_slot'];

try {
    $stmt = $pdo->prepare("UPDATE appointments SET service=:service, date=:date, time_slot=:time_slot WHERE id=:id AND user_id=:user_id");
    $stmt->execute([
        ':service'=>$service,
        ':date'=>$date,
        ':time_slot'=>$time_slot,
        ':id'=>$id,
        ':user_id'=>$_SESSION['user_id']
    ]);

    echo json_encode(['status'=>'success']);
} catch(PDOException $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
