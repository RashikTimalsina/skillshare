<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';

$errors = [];
$success = false;

$token = $_GET['token'] ?? '';
$token = trim($token);

if (!$token) {
    $errors[] = "Invalid or missing reset token.";
} else {

    // Validate token
    $stmt = $conn->prepare("
        SELECT pr.user_id 
        FROM password_resets pr
        WHERE pr.token = ? 
        AND pr.expires_at > NOW()
        LIMIT 1
    ");

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $errors[] = "This reset link is invalid or expired.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $upd->bind_param("si", $hash, $user['user_id']);
        $upd->execute();
        $upd->close();

        // Delete used token
        $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $del->bind_param("s", $token);
        $del->execute();
        $del->close();

        $success = true;
    }
}
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Reset Password — SkillShare</title>
    <link rel="stylesheet" href="../assets/css/pages/forget_password.css">
</head>

<body>
    <div class="topbar"><span class="brand">SkillShare</span></div>

    <div class="login-wrapper">
        <form class="login-form" method="POST" novalidate>
            <h2>Reset Password</h2>

            <?php if ($success): ?>
                <div class="msg success">
                    Password updated successfully. <a href="login.php">Login now</a>.
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="msg error">
                        <ul style="margin:0; padding-left:18px;">
                            <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="New password" style="" required>
                </div>
                <div class="field">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>
                </div>

                <div class="actions">
                    <button type="submit" class="btn">Reset Password</button>
                </div>
            <?php endif; ?>

            <div class="footer-note">
                <a href="login.php">Back to Login</a><br>
                <a href="../index.php">Back to Home Page</a>
            </div>
        </form>
    </div>
</body>

</html>