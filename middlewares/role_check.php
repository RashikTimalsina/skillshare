<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

function allowRoles(array $allowedRoles)
{
    if (empty($_SESSION['user_role'])) {
        header("Location: ../auth/login.php");
        exit();
    }
    if (!in_array($_SESSION['user_role'], $allowedRoles, true)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}
