<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = $_POST['appointment_id'];
    $sql = "UPDATE appointments SET status='Cancelled' WHERE id='$appointment_id'";
    $conn->query($sql);
    echo "Appointment cancelled successfully.";
}
?>
