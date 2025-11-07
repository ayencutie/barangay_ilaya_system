<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $service = $_POST['service'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];

    $sql = "INSERT INTO appointments (user_id, service, date, time_slot, status) 
            VALUES ('$user_id', '$service', '$date', '$time_slot', 'Pending')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href='../my_appointment.html';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
