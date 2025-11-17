<?php
// php/admin/serve_patient_file.php
session_start();
require __DIR__ . '/../db.php';

// ============================
// ADMIN VALIDATION
// ============================
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// ============================
// GET INPUTS
// ============================
$patientFolder = $_GET['patient'] ?? '';
$file = $_GET['file'] ?? '';
$download = isset($_GET['download']); // true if ?download=1

if (!$patientFolder || !$file) {
    http_response_code(400);
    exit('Invalid request');
}

// Disallow dangerous characters
if (preg_match('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]/', $file)) {
    http_response_code(400);
    exit('Invalid filename');
}

// ============================
// PATH RESOLUTION
// ============================
$base = realpath(__DIR__ . '/../patients_completed');  // <== your current folder
$folder = realpath($base . '/' . $patientFolder);

// Folder must exist AND stay inside base folder
if (!$folder || strpos($folder, $base) !== 0) {
    http_response_code(404);
    exit('Patient folder not found');
}

$path = $folder . '/' . $file;

// File must exist
if (!file_exists($path)) {
    http_response_code(404);
    exit('File not found');
}

// ============================
// FILE RESPONSE
// ============================
$mime = mime_content_type($path) ?: 'application/octet-stream';
$basename = basename($path);

if ($download) {
    // Force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$basename.'"');
} else {
    // Preview inline
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="'.$basename.'"');
}

header('Content-Length: ' . filesize($path));
readfile($path);
exit;

?>
