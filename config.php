<?php
/**
 * Database Configuration File
 * Sets up database connection constants for the ERP application
 */

// Database Configuration Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'transportation_erp');

// Application Configuration
define('APP_NAME', 'Transportation ERP System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/transportation_erp');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
