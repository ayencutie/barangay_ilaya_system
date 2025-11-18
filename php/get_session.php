<?php
// Start session in read-only mode and close immediately to avoid blocking
if (PHP_VERSION_ID >= 70300) {
    session_start(['read_and_close' => true]);
} else {
    session_start();
}
header('Content-Type: application/json');

$resp = [
    'authenticated' => false,
    'user_role' => null
];

if (isset($_SESSION['patient_id'])) {
    $resp['authenticated'] = true;
    $resp['user_role'] = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
}

echo json_encode($resp);
?>