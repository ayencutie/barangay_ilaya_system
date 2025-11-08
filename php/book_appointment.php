<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $service = $_POST['service'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];

    try {
        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service, date, time_slot, status)
                               VALUES (:user_id, :service, :date, :time_slot, 'Pending')");
        $stmt->execute([
            ':user_id' => $user_id,
            ':service' => $service,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Appointment booked successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
