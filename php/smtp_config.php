<?php
// SMTP configuration template for sending OTP via SMTP (Gmail or other provider)
// Copy this file to the same folder and fill in your SMTP credentials.

// Example using Gmail with App Passwords (recommended):
// - Create an App Password in your Google Account (2FA required)
// - Use smtp.gmail.com, port 587, SMTPSecure 'tls'

return [
    // Enable SMTP sending. Set to true to use PHPMailer + SMTP.
    'enabled' => true,

    // SMTP server settings
    'host' => 'smtp.gmail.com',
    'username' => 'healthcenterilaya@gmail.com',              // <-- put your Gmail address here
    'password' => 'rcupzlnellbvgyvs',   // <-- put the App Password here
    'port' => 587,
    'secure' => 'tls', // 'tls' or 'ssl'

    // From address shown to recipients
    'from_email' => 'no-reply@yourdomain.com',
    'from_name' => 'Barangay Ilaya Health Center',

    // Additional options
    'smtp_auto_tls' => true, // let PHPMailer enable TLS automatically
];

