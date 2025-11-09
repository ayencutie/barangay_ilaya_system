<?php
session_start();
include 'db.php'; // path relative to this file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['login_error'] = "Account not found.";
            header("Location: ../login.php");
            exit;
        }

        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['account_id'] = $user['account_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            // --- Admin side: store user role ---
            $_SESSION['user_role'] = $user['user_role']; // Make sure 'user_role' column exists in your users table

            unset($_SESSION['login_error']);

            // --- Redirect based on role ---
            if ($user['user_role'] === 'admin') {
                header("Location: ../admin/dashboard.php"); // Admin goes to admin dashboard
            } else {
                header("Location: ../index.html"); // redirect to dashboard
            }
            exit;
        } else {
            $_SESSION['login_error'] = "Wrong password.";
            header("Location: ../login.php");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Database error: " . $e->getMessage();
        header("Location: ../login.php");
        exit;
    }
}
