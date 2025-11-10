<?php
session_start();
require 'db.php';

if(!isset($_GET['date']) || !isset($_GET['service'])){
    echo json_encode([]);
    exit;
}

$date = $_GET['date'];
$service = $_GET['service'];

// Fetch booked time slots for this date & service
$stmt = $pdo->prepare("SELECT time_slot FROM appointments WHERE date = :date AND service = :service");
$stmt->execute([
    ':date' => $date,
    ':service' => $service
]);

$booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($booked);
