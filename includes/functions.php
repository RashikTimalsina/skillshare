<?php
function checkRole($requiredRole)
{
    // Ensure session is started at the top of the page
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: /index.php");
        exit();
    }

    // Redirect if user role does not match required
    if ($_SESSION['user_role'] !== $requiredRole) {
        switch ($_SESSION['user_role']) {
            case 'admin':
                header("Location: ../dashboard/admin_dashboard.php");
                break;
            case 'teacher':
                header("Location: ../dashboard/teacher_dashboard.php");
                break;
            case 'learner':
                header("Location: ../dashboard/learner_dashboard.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
    }
}
function logActivity($conn, $user_id, $username, $action)
{
    $user_id = (int)$user_id;
    $username = mysqli_real_escape_string($conn, $username);
    $action = mysqli_real_escape_string($conn, $action);

    $sql = "INSERT INTO activities (user_id, username, action) VALUES ($user_id, '$username', '$action')";
    mysqli_query($conn, $sql);
}
