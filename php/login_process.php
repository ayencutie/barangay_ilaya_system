<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['login_error'] = "Account not found.";
        header("Location: ../login.php");
        exit;
    }

    $dbPassword = $user['password'];
    $isValid = false;

    // Bcrypt
    if (substr($dbPassword, 0, 4) === '$2y$') {
        if (password_verify($password, $dbPassword)) {
            $isValid = true;
        }
    }
    // SHA1 fallback
    elseif (strlen($dbPassword) === 40 && ctype_xdigit($dbPassword)) {
        if (sha1($password) === $dbPassword) {
            $isValid = true;

            // Upgrade password to bcrypt
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->execute([$newHash, $email]);
        }
    }

    if (!$isValid) {
        $_SESSION['login_error'] = "Wrong password.";
        header("Location: ../login.php");
        exit;
    }

    // 👉 SET CORRECT SESSION VARIABLES
    $_SESSION['user_id'] = $user['patient_id'];  // used by _auth_admin.php
    $_SESSION['role'] = $user['user_role'];       // used by _auth_admin.php
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];

    // Admin redirect
    if ($user['user_role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    }

    // Patient redirect
    header("Location: ../index.html");
    exit;
}
?>
