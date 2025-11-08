<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id'])){ exit('Error: not logged in'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $user_id = $_SESSION['user_id'];
    $old = trim($_POST['old_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if($new !== $confirm){ exit('<div class="error-message">Passwords do not match</div>'); }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=:id");
    $stmt->execute([':id'=>$user_id]);
    $user = $stmt->fetch();

    if(!$user || !password_verify($old,$user['password'])){
        exit('<div class="error-message">Old password is incorrect</div>');
    }

    $hashed = password_hash($new,PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password=:pwd WHERE id=:id");
    $stmt->execute([':pwd'=>$hashed, ':id'=>$user_id]);

    exit('<div class="success-message">Password updated successfully</div>');
}
?>
