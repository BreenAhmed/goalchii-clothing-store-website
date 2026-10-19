<?php
// Database configuration

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u100517965_goalchii0751');
define('DB_USER', 'u100517965_goalchii0751');
define('DB_PASS', 'Brezzo#0751');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    try {
        // Use 127.0.0.1 instead of localhost on macOS to force TCP connection
        $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Get single instance of database connection
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    return $pdo;
}
