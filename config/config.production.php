<?php
/**
 * Production Configuration Template for Routina
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to config.php on your production server
 * 2. Update all values below with your hosting details
 * 3. NEVER commit this file with real credentials to git
 */

return [
    'app_name' => 'Routina',
    'app_url' => 'https://your-domain.com',  // Your actual domain

    // Admin access control
    'admin_emails' => ['your-admin-email@example.com'],
    
    // Database Configuration (MySQL for shared hosting)
    'db_connection' => 'mysql',
    'db_host' => 'localhost',              // Check cPanel for actual MySQL host
    'db_port' => 3306,
    'db_name' => 'your_database_name',     // Your database name
    'db_user' => 'your_database_user',     // Your database username
    'db_pass' => 'your_database_password', // Your database password

    // Google OAuth Configuration (update redirect_uri for production)
    // Get credentials from: https://console.cloud.google.com/apis/credentials
    'google_client_id' => 'your-google-client-id.apps.googleusercontent.com',
    'google_client_secret' => 'your-google-client-secret',
    'google_redirect_uri' => 'https://your-domain.com/auth/google/callback',

    // Email Configuration (for password reset emails)
    'mail_from' => 'noreply@your-domain.com',
    'mail_from_name' => 'Routina',
    // SMTP settings (optional - if empty, uses PHP mail())
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
];
