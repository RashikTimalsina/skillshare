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
    header("Location: categories.php");
    exit();
}

$categoryId = $_GET['id'];

// Optional: Check if any skills are using this category
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM skills WHERE categories_category_id=?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res['total'] > 0) {
    die("Cannot delete category. There are skills assigned to this category.");
}

$stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
$stmt->bind_param("i", $categoryId);

if ($stmt->execute()) {
    header("Location: categories.php?msg=Category deleted successfully");
} else {
    die("Error deleting category: " . $stmt->error);
}
