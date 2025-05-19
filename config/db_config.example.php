<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username');  // Replace with your database username
define('DB_PASSWORD', 'your_password');  // Replace with your database password
define('DB_NAME', 'farmknowledge_db');

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?> 