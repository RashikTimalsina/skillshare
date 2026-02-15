<?php
// pages/enroll_skill.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'none';
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;

// Allow only learners to enroll
if ($user_role !== 'learner') {
    $_SESSION['error_message'] = "Only learners can enroll in skills.";
    header("Location: /pages/browse_skills.php");
    exit();
}

// Validate skill ID
if ($skill_id <= 0) {
    $_SESSION['error_message'] = "Invalid skill selected.";
    header("Location: /pages/browse_skills.php");
    exit();
}

// Check if already enrolled
$check_sql = "SELECT * FROM enrollments WHERE users_user_id = ? AND skills_skill_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $skill_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['info_message'] = "You are already enrolled in this skill.";
    header("Location: /dashboard/learner/enrollments.php");
    exit();
}

// Insert new enrollment
$insert_sql = "INSERT INTO enrollments (users_user_id, skills_skill_id, status, enrolled_at)
               VALUES (?, ?, 'enrolled', NOW())";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("ii", $user_id, $skill_id);

if ($stmt->execute()) {

    // ================================
    // AUTOMATIC NOTIFICATION (ENROLLMENT)
    // ================================

    // find teacher who owns this skill
    $teacher_sql = "
        SELECT users_user_id 
        FROM teachers 
        WHERE skills_skill_id = ? 
        AND status = 'active'
        LIMIT 1
    ";

    $teacher_stmt = $conn->prepare($teacher_sql);
    $teacher_stmt->bind_param("i", $skill_id);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();

    if ($teacher_result && $teacher_result->num_rows === 1) {
        $teacher = $teacher_result->fetch_assoc();
        $teacher_id = (int) $teacher['users_user_id'];
    }

    $_SESSION['success_message'] = "Successfully enrolled in the skill!";
    header("Location: /dashboard/learner/enrollments.php");
    exit();
}
