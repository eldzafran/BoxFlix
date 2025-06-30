<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boxflix');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Session start
session_start();

// Theme colors
define('PRIMARY_COLOR', '#1460b8');
define('SECONDARY_COLOR', '#171515');

// Subscription prices
define('MONTHLY_PRICE', 27000);
define('YEARLY_PRICE', 250000);

// Base directory
define('BASE_DIR', dirname(__FILE__));

// File upload paths (absolute paths for storage)
define('UPLOAD_POSTER_PATH', BASE_DIR . '/uploads/posters/');
define('UPLOAD_VIDEO_PATH', BASE_DIR . '/uploads/videos/');

// File URL paths (relative paths for display)
define('UPLOAD_POSTER_URL', 'uploads/posters/');
define('UPLOAD_VIDEO_URL', 'uploads/videos/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_POSTER_PATH)) {
    mkdir(UPLOAD_POSTER_PATH, 0777, true);
}
if (!file_exists(UPLOAD_VIDEO_PATH)) {
    mkdir(UPLOAD_VIDEO_PATH, 0777, true);
}
?> 