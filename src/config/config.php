<?php
// Load environment variables from .env file if present
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(sprintf('%s=%s', trim($name), trim($value)));
    }
}

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'cdn_test');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Authentication settings
define('AUTH_SECRET', $_ENV['AUTH_SECRET'] ?? 'default-secret-key-change-this');
define('SESSION_NAME', 'cdn_test_session');

// Application paths
define('BASE_URL', '/'); // Change if in subdirectory
define('UPLOAD_DIR', __DIR__ . '/../../public/uploads/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 10MB

// Supported file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);
