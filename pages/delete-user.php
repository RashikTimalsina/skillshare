<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');

// Only admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$userId = $_GET['id'];

// Optional: prevent admin from deleting themselves
if ($_SESSION['user_id'] == $userId) {
    die("You cannot delete yourself.");
}

$stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    header("Location: users.php?msg=User deleted successfully");
} else {
    die("Error deleting user: " . $stmt->error);
}
