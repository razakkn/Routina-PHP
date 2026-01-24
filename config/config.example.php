<?php

// Copy this file to config.php and fill in your local settings.

return [
    'app_name' => 'Routina',
    'app_url' => 'http://localhost',

    // Admin access control
    'admin_emails' => ['demo@routina.com'],

    // Database Configuration
    // Options: 'sqlite', 'mysql', 'pgsql'
    'db_connection' => 'pgsql',
    'db_host' => '127.0.0.1',
    // Set to 0 to use the driver's default (pgsql=5432, mysql=3306)
    'db_port' => 5432,
    'db_name' => 'routina',
    'db_user' => 'routina',
    'db_pass' => '',
];
