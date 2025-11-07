<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

if (isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $result = $conn->query("SELECT password FROM users WHERE id='$user_id'");
    $row = $result->fetch_assoc();

    if (password_verify($old_password, $row['password'])) {
        $conn->query("UPDATE users SET password='$new_password' WHERE id='$user_id'");
        echo "Password updated successfully.";
    } else {
        echo "Incorrect old password.";
    }
}

if (isset($_POST['update_phone'])) {
    $password = $_POST['password'];
    $new_phone = $_POST['new_phone'];

    $result = $conn->query("SELECT password FROM users WHERE id='$user_id'");
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {
        $conn->query("UPDATE users SET phone='$new_phone' WHERE id='$user_id'");
        echo "Phone number updated successfully.";
    } else {
        echo "Incorrect password.";
    }
}
?>
