<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['account_id'])) {
    echo json_encode([]);
    exit();
}

$account_id = $_SESSION['account_id'];

$stmt = $pdo->prepare("SELECT service, date, time_slot, status 
                       FROM appointments 
                       WHERE account_id = :account_id 
                       ORDER BY date DESC");
$stmt->execute([':account_id' => $account_id]);

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($appointments);
?>
