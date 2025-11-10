<?php
session_start();
require 'db.php';

if(!isset($_SESSION['account_id'])){
    die("You must be logged in to book an appointment.");
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $account_id = $_SESSION['account_id'];
    $service = $_POST['service'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];

    try {
        $stmt = $pdo->prepare("INSERT INTO appointments (account_id, service, date, time_slot) VALUES (:account_id, :service, :date, :time_slot)");
        $stmt->execute([
            ':account_id' => $account_id,
            ':service' => $service,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);

        echo "<script>alert('Appointment booked successfully!'); window.location='../my_appointment.html';</script>";
    } catch(PDOException $e){
        die("Error: ".$e->getMessage());
    }
}
?>
