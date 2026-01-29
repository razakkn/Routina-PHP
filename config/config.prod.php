<?php

return [
    'app_name' => 'Routina',
    'app_url' => 'https://your-domain.example',

    // Admin access control
    'admin_emails' => ['demo@routina.com'],
    
    // Database Configuration - MySQL for shared hosting
    'db_connection' => 'mysql',
    'db_host' => 'your-mysql-host',
    'db_port' => 3306,
    'db_name' => 'your_database_name',
    'db_user' => 'your_database_user',
    'db_pass' => 'your_database_password',

    // Google OAuth Configuration
    'google_client_id' => 'your-google-client-id.apps.googleusercontent.com',
    'google_client_secret' => 'your-google-client-secret',
    'google_redirect_uri' => 'https://your-domain.example/auth/google/callback',

    // Email Configuration (for password reset emails)
    'mail_from' => 'noreply@routina.app',
    'mail_from_name' => 'Routina',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
];
