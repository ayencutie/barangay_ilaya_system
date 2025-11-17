<?php
require 'db.php';
header('Content-Type: application/json');
session_start();

// Return list of patients for admin left list
$stmt = $pdo->prepare("SELECT patient_id, first_name, last_name FROM users WHERE 1 ORDER BY first_name");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
