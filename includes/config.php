<?php

// Start session (required for authentication)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// includes/config.php
// Database connection - update credentials for your environment.

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'CLEB2d@mysql';
$DB_NAME = 'skillshare';

// Create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Make mysqli return associative arrays by default (optional)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
