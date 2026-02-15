<?php
// Ensure teacher cannot perform actions before admin approval
session_start();
require_once __DIR__ . '/../includes/config.php';

// Only apply to teachers
if ($_SESSION['user_role'] === 'teacher') {
    $teacherId = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT status FROM teachers WHERE users_user_id = ? LIMIT 1");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    $teacher = $res->fetch_assoc();
    $stmt->close();

    if (!$teacher || $teacher['status'] !== 'active') {
        $_SESSION['error'] = "Your account approval is pending. You cannot perform this action until admin approval.";
        header("Location: ../dashboard/teacher_dashboard.php");
        exit();
    }
}
