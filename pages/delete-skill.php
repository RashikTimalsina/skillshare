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
    header("Location: skills.php");
    exit();
}

$skillId = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM skills WHERE skill_id=?");
$stmt->bind_param("i", $skillId);

if ($stmt->execute()) {
    header("Location: skills.php?msg=Skill deleted successfully");
} else {
    die("Error deleting skill: " . $stmt->error);
}
