<?php
// Optional SMTP/PHPMailer configuration.
// Copy this file to enable SMTP and configure values.
return [
    // Set to true to enable PHPMailer or SimpleSMTP usage
    'use_smtp' => true,

    // SMTP settings (used when use_smtp = true)
    'host' => 'smtp.gmail.com',         // Gmail SMTP server
    'username' => 'josefdagne5@gmail.com',
    'password' => 'nwjn kzrt zysf eyah', // App Password
    'port' => 587,                      // TLS port
    'encryption' => 'tls',              // tls or ssl
    'from_email' => 'josefdagne5@gmail.com', // Should match username for Gmail
    'from_name' => 'UniConnect',

    // Optional: set to true to verify server certificates (default true)
    'smtp_verify' => true,
];
