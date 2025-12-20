<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'foodapp_db');

// Google Maps API key (set your key here)
// You can also leave this empty and set via environment or other secure mechanism.
define('GOOGLE_MAPS_KEY', 'AIzaSyCUpQUm8t4TYREw2F18ouedMjiIiB9NUoU');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper encoding
$conn->set_charset("utf8mb4");

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if ($conn->query($sql) === TRUE) {
    // Select the database
    $conn->select_db(DB_NAME);
} else {
    die("Error creating database: " . $conn->error);
}
?>
