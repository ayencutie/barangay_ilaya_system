<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_id = $_SESSION['user_id'];
    $message = $_POST['message'];
    $type = $_POST['type']; // inbox, reminder, etc.

    $sql = "INSERT INTO messages (sender_id, content, type, created_at)
            VALUES ('$sender_id', '$message', '$type', NOW())";

    if ($conn->query($sql) === TRUE) {
        echo "Message sent successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
