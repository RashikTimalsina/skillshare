<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/flash_messages.php';

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;

if ($userRole !== 'teacher') {
    header("Location: ../unauthorized.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT status 
    FROM teachers 
    WHERE users_user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || $result['status'] !== 'active') {
    setFlash('teacher_rewards','warning', 'Your teacher account is not approved yet.');
    header("Location: ../dashboard/teacher_dashboard.php");
    exit;
}
