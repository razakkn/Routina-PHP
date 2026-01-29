<?php
// Lightweight web wrapper to run setup_database.php.
// Usage: /run_setup.php

// Prevent running via CLI accidentally
if (php_sapi_name() === 'cli') {
    echo "This endpoint is intended for web access only.\n";
    exit;
}

// Run the setup script and stream output
$script = __DIR__ . '/../setup_database.php';
if (!is_file($script)) {
    http_response_code(500);
    echo "setup_database.php not found.\n";
    exit;
}

// Include the script (it outputs plain text). Use output buffering to ensure clean output.
header('Content-Type: text/plain; charset=utf-8');
include $script;

exit;
