<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force logout if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit();
}
