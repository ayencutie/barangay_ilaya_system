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
    // 1. Get current picture path and gender
    $stmt = $pdo->prepare("SELECT profile_pic, gender FROM users WHERE patient_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $current = $row['profile_pic'];
    $gender = strtolower($row['gender'] ?? '');

    // 2. Default path
    if ($gender === 'male' || $gender === 'm') {
        $default = 'uploads/default_male.svg';
    } elseif ($gender === 'female' || $gender === 'f') {
        $default = 'uploads/default_female.svg';
    } else {
        $default = 'uploads/default_profile.png';
    }

    // 3. Default pictures (do not delete)
    $defaults = [
        'uploads/default_profile.png',
        'uploads/default_male.svg',
        'uploads/default_female.svg'
    ];

    // FIX: Remove leading slash from DB value
    $clean = ltrim($current, '/');

    // FIX: Safe absolute path
    $full_path_to_current = realpath(__DIR__ . '/../' . $clean);

    // Delete old file only if:
    // - may file
    // - hindi default
    // - valid ang real path
    if ($full_path_to_current && !in_array($current, $defaults)) {

        if (file_exists($full_path_to_current)) {

            if (is_writable($full_path_to_current)) {

                @unlink($full_path_to_current);

            } else {
                error_log("REMOVE_PERMISSION_FAIL: " . $full_path_to_current);
            }
        }
    }

    // 4. Update DB to default
    $stmt = $pdo->prepare("UPDATE users SET profile_pic = :pic WHERE patient_id = :pid");
    $stmt->execute([':pic' => $default, ':pid' => $patient_id]);

    echo json_encode(['status' => 'success', 'path' => $default]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
