<?php
// test_smtp.php — send a test email using php/smtp_config.php and PHPMailer
// Usage: open in browser or run `php php/test_smtp.php` from project root
require __DIR__ . '/db.php';
$smtp = include __DIR__ . '/get_smtp_config.php';
if (empty($smtp['enabled'])) {
    echo "SMTP not enabled. Set environment variables or enable smtp_config.php.\n";
    exit;
}
$vendor = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendor)) {
    echo "PHPMailer not installed. Run: composer require phpmailer/phpmailer\n";
    exit;
}
require_once $vendor;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$to = $smtp['username']; // send test to same account
$subject = 'Test SMTP from Barangay Ilaya';
$body = "This is a test email from your local app. If you received this, SMTP is configured correctly.";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->SMTPSecure = $smtp['secure'] ?? 'tls';
    $mail->Port = $smtp['port'] ?? 587;
    $mail->SMTPAutoTLS = $smtp['smtp_auto_tls'] ?? true;
    $mail->setFrom($smtp['from_email'] ?? $smtp['username'], $smtp['from_name'] ?? 'No Reply');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();
    echo "OK: email sent to $to\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
