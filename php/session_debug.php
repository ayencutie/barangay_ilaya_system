<?php
// session_debug.php — development helper to inspect session contents
// WARNING: Only use this locally for debugging. Remove or protect in production.
session_start();
header('Content-Type: text/plain');

echo "SESSION DATA:\n";
print_r($_SESSION);

// Also show recent POST if any
if (!empty($_POST)) {
    echo "\nPOST DATA:\n";
    print_r($_POST);
}

// Show a simple link back
echo "\nOpen the OTP verify page: ../php/otp_verify.php\n";
?>