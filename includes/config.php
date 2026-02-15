<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kathmandu');


// Database credentials
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'CLEB2d@mysql';
$DB_NAME = 'skillshare';

// Create DB connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset and error mode
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
