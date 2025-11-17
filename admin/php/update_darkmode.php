<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo "not logged in";
    exit;
}

$mode = isset($_POST['dark_mode']) ? intval($_POST['dark_mode']) : 0;

$stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE patient_id = ?");
$stmt->execute([$mode, $_SESSION['user_id']]);

echo "saved";
