<?php
session_start();

// If any user is logged in, destroy the session immediately
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
}
