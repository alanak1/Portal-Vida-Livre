<?php

declare(strict_types=1);

return [
    'host' => env('SMTP_HOST', 'smtp.gmail.com'),
    'port' => (int) env('SMTP_PORT', '587'),
    'encryption' => env('SMTP_ENCRYPTION', 'tls'),
    'username' => env('SMTP_USERNAME', 'seu-email@gmail.com'),
    'password' => env('SMTP_PASSWORD', ''),
    'from_email' => env('SMTP_FROM_EMAIL', 'seu-email@gmail.com'),
    'from_name' => env('SMTP_FROM_NAME', 'Portal Vida Livre'),
];

