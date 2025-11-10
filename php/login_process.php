<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email'=>$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['login_error'] = "Account not found.";
        header("Location: ../login.php");
        exit;
    }

    if (password_verify($password, $user['password'])) {
        $_SESSION['account_id'] = $user['account_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['user_role'] = $user['user_role'];

        if ($user['user_role']==='admin') {
            header("Location: /myphp/barangay_ilaya_system/admin/dashboard.php");
        } else {
            header("Location: /barangay_ilaya_system/index.html");
        }
        exit;
    } else {
        $_SESSION['login_error'] = "Wrong password.";
        header("Location: ../login.php");
        exit;
    }
}
?>
