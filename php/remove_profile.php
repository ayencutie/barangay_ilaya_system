<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$patient_id = $_SESSION['patient_id'];

try {
    $stmt = $pdo->prepare("SELECT profile_pic, gender FROM users WHERE patient_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $current = $row['profile_pic'];
    $gender = strtolower($row['gender'] ?? '');

    // determine default path based on gender
    if ($gender === 'male' || $gender === 'm') {
        $default = 'uploads/default_male.svg';
    } elseif ($gender === 'female' || $gender === 'f') {
        $default = 'uploads/default_female.svg';
    } else {
        $default = 'uploads/default_profile.png';
    }

    // remove current file if it exists and isn't one of the defaults
    $defaults = ['uploads/default_profile.png','uploads/default_male.svg','uploads/default_female.svg'];
    if ($current && !in_array($current, $defaults)) {
        $full = __DIR__ . '/../' . $current;
        if (file_exists($full)) @unlink($full);
    }

    $stmt = $pdo->prepare("UPDATE users SET profile_pic = :pic WHERE patient_id = :pid");
    $stmt->execute([':pic' => $default, ':pid' => $patient_id]);

    echo json_encode(['status' => 'success', 'path' => $default]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>