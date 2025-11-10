<?php
session_start();
require 'db.php';

if(!isset($_SESSION['account_id'])){
    echo json_encode(['error'=>'Not logged in']);
    exit;
}

$account_id = $_SESSION['account_id']; // Use account_id from session

$stmt = $pdo->prepare("SELECT account_id, first_name, last_name, address, email, phone FROM users WHERE account_id=:account_id");
$stmt->execute([':account_id'=>$account_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    echo json_encode(['error'=>'User not found']);
    exit;
}

echo json_encode($user);
?>
