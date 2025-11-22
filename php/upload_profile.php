<?php
// I-enable ang error reporting para makita kung may syntax error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php'; // Siguraduhin na tama ang path (nasa parehong folder ba?)

header('Content-Type: application/json');

// 1. CHECK USER AUTHENTICATION
if (!isset($_SESSION['patient_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

$patient_id = $_SESSION['patient_id']; 
$upload_dir = '../uploads/'; // Ito ang folder sa labas ng php/ folder

// 2. CHECK FILE UPLOAD
if (!isset($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['profile']['error'] ?? 'No file';
    echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $errCode]);
    exit;
}

$file = $_FILES['profile'];
$max_size = 5 * 1024 * 1024; // 5 MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// 3. VALIDATION
if ($file['size'] > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit.']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP']);
    exit;
}

// 4. PREPARE DESTINATION
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
// Gumawa ng unique filename para iwas cache issue
$filename = 'profile_' . $patient_id . '_' . time() . '.' . $ext;
$targetFull = $upload_dir . $filename; // Absolute path sa server
$dbPath = 'uploads/' . $filename;      // Path na ise-save sa database

// 5. CREATE FOLDER IF NOT EXISTS
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create uploads directory.']);
        exit;
    }
}

// 6. MOVE FILE
if (!move_uploaded_file($file['tmp_name'], $targetFull)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move file to ' . $targetFull]);
    exit;
}

// 7. UPDATE DATABASE
try {
    // Kunin ang lumang picture para burahin
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE patient_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $patient_id]);
    $old = $stmt->fetchColumn();

    // I-update sa bago
    $stmt = $pdo->prepare("UPDATE users SET profile_pic = :pic WHERE patient_id = :pid");
    $stmt->execute([':pic' => $dbPath, ':pid' => $patient_id]);

    // Burahin ang lumang file (optional, pero maganda para di mapuno storage)
    $defaults = ['uploads/default_profile.png', 'uploads/default_male.svg', 'uploads/default_female.svg'];
    if ($old && !in_array($old, $defaults)) {
        $oldFile = '../' . $old;
        if (file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>