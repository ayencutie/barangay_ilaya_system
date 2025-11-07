<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['account_id'] = $row['account_id'];
            $_SESSION['login_attempts'] = 0;
            header("Location: ../index.html");
            exit();
        } else {
            $_SESSION['login_attempts']++;
        }
    } else {
        $_SESSION['login_attempts']++;
    }

    if ($_SESSION['login_attempts'] >= 3) {
        echo "<script>alert('Your account is locked. Please contact the admin.'); window.location.href='../login.html';</script>";
    } else {
        echo "<script>alert('Invalid login. Attempts left: " . (3 - $_SESSION['login_attempts']) . "'); window.location.href='../login.html';</script>";
    }
}
?>
