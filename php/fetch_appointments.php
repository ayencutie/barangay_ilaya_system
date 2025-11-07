<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM appointments WHERE user_id='$user_id' ORDER BY date DESC";
$result = $conn->query($sql);

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode($appointments);
?>
