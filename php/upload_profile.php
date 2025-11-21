<?php
session_start();
require 'db.php'; // Siguraduhin na tama ang path papunta sa db.php

header('Content-Type: application/json');

// 1. CHECK USER AUTHENTICATION: Ginagamit ang 'patient_id'
if (!isset($_SESSION['patient_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

$patient_id = $_SESSION['patient_id']; 
$upload_dir = '../uploads/'; // IMPORTANT: Path papunta sa uploads/ folder (galing sa php/ folder)

// 2. CHECK FILE UPLOAD
if (!isset($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['profile'];
$max_size = 5 * 1024 * 1024; // 5 MB max size
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// 3. VALIDATION
if ($file['size'] > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed.']);
    exit;
}

// 4. GENERATE UNIQUE FILENAME AND PATH
$safePatient = preg_replace('/[^a-zA-Z0-9_-]/', '', $patient_id);
$filename = 'profile_' . $safePatient . '_' . time() . '.' . $ext;
$targetFull = $upload_dir . $filename; // Full path sa server
$dbPath = 'uploads/' . $filename;       // Path na ise-save sa database (relative to root)

// 5. CHECK DIRECTORY AND MOVE UPLOADED FILE
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create uploads directory. Check folder permissions.']);
        exit;
    }
}

if (!move_uploaded_file($file['tmp_name'], $targetFull)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check folder permissions.']);
    exit;
}

// 6. UPDATE DATABASE AND DELETE OLD FILE
try {
    // 6a. Select old file path
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE patient_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $patient_id]);
    $old = $stmt->fetchColumn();

    // 6b. Update database with new path
    $stmt = $pdo->prepare("UPDATE users SET profile_pic = :pic WHERE patient_id = :pid");
    $stmt->execute([':pic' => $dbPath, ':pid' => $patient_id]);

    // 6c. Delete old file if it exists and is not a default file
    $oldFull = '../' . $old; // Path galing sa php/ folder
    $defaults = ['uploads/default_profile.png', 'uploads/default_male.svg', 'uploads/default_female.svg'];

    if ($old && !in_array($old, $defaults) && file_exists($oldFull)) {
        @unlink($oldFull);
    }

    echo json_encode(['status' => 'success', 'path' => $dbPath]);
} catch (Exception $e) {
    // If DB update fails, delete the file just uploaded
    if (file_exists($targetFull)) @unlink($targetFull); 
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>