<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$patient_id = $_SESSION['patient_id'];

if (!isset($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile'];
$allowedExt = ['jpg','jpeg','png','gif','webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create uploads directory']);
        exit;
    }
}

$safePatient = preg_replace('/[^a-zA-Z0-9_-]/', '', $patient_id);
$filename = 'profile_' . $safePatient . '_' . time() . '.' . $ext;
$targetFull = $uploadDir . $filename;
$dbPath = 'uploads/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetFull)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    exit;
}

try {
    // remove old file if set and not default
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE patient_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $patient_id]);
    $old = $stmt->fetchColumn();
    if ($old && $old !== 'uploads/default_profile.png' && $old !== 'uploads/default_male.svg' && $old !== 'uploads/default_female.svg') {
        $oldFull = __DIR__ . '/../' . $old;
        if (file_exists($oldFull)) @unlink($oldFull);
    }

    $stmt = $pdo->prepare("UPDATE users SET profile_pic = :pic WHERE patient_id = :pid");
    $stmt->execute([':pic' => $dbPath, ':pid' => $patient_id]);

    echo json_encode(['status' => 'success', 'path' => $dbPath]);
} catch (Exception $e) {
    if (file_exists($targetFull)) @unlink($targetFull);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
