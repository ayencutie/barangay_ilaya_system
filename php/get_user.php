<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id'])){
    echo json_encode(['error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT account_id, first_name, last_name, address, email, phone FROM users WHERE id=:id");
$stmt->execute([':id'=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    echo json_encode(['error'=>'User not found']);
    exit;
}

echo json_encode($user);
