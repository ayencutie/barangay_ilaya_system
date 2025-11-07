<?php
session_start();
require 'db.php'; // ✅ tama na connection file

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
        // ✅ insert using PDO
        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service, date, time_slot, status)
                               VALUES (:user_id, :service, :date, :time_slot, 'Pending')");
        $stmt->execute([
            ':user_id' => $user_id,
            ':service' => $service,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);

        echo "<script>alert('Appointment booked successfully!'); window.location.href='../my_appointment.html';</script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
