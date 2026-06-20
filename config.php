<?php
/**
 * Application Bootstrap Configuration
 * Loads environment variables and initializes the app
 */

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file(__DIR__ . '/.env');

    foreach ($envFile as $line) {
        $line = trim($line);

        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set as environment variable
            putenv("$key=$value");
        }
    }
}

// Initialize database connection
require_once __DIR__ . '/db.php';

// Session configuration - MUST be before session_start()
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON response header (for API endpoints)
header('Content-Type: application/json');

// CORS headers (optional - adjust for your needs)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
