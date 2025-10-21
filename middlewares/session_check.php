<?php
// session_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force logout if session not set
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();

}
