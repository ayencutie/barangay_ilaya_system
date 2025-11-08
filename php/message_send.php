<?php
session_start();
require 'db.php'; // siguraduhing tama ang path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        echo "Error: User not logged in.";
        exit();
    }

    $sender_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);
    $type = isset($_POST['type']) ? $_POST['type'] : 'inbox'; // default inbox

    try {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, content, type, created_at)
            VALUES (:sender_id, :content, :type, NOW())
        ");

        $stmt->execute([
            ':sender_id' => $sender_id,
            ':content' => $message,
            ':type' => $type
        ]);

        echo "<script>alert('Message sent successfully!'); window.location.href='inbox.php';</script>";
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
}
?>
