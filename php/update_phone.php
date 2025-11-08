<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id'])){ exit('Error: not logged in'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $user_id = $_SESSION['user_id'];
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if(!preg_match('/^\d{11}$/',$phone)){
        exit('<div class="error-message">Invalid phone number</div>');
    }

    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=:id");
    $stmt->execute([':id'=>$user_id]);
    $user = $stmt->fetch();

    if(!$user || !password_verify($password,$user['password'])){
        exit('<div class="error-message">Wrong password</div>');
    }

    // Update phone
    $stmt = $pdo->prepare("UPDATE users SET phone=:phone WHERE id=:id");
    $stmt->execute([':phone'=>$phone, ':id'=>$user_id]);

    exit('<div class="success-message">Phone updated successfully</div>');
}
?>
