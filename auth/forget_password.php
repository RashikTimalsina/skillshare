<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {

        // Cleanup expired tokens
        $conn->query("DELETE FROM password_resets WHERE expires_at < NOW()");

        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            // Delete old token for this user (only 1 active reset allowed)
            $delOld = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delOld->bind_param("i", $user['user_id']);
            $delOld->execute();
            $delOld->close();

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token
            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user['user_id'], $token, $expires);
            $ins->execute();
            $ins->close();

            // Build dynamic base URL (works in subfolder & production)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));

            $resetLink = $baseUrl . "/auth/reset_password.php?token=" . urlencode($token);

            // DEV MODE: show clickable link
            $message = "If this email exists, a reset link has been generated.<br><br>";
            $message .= "<a href='{$resetLink}'>Click here to reset password</a>";

            // PRODUCTION:
            // mail($email, "Reset Your Password", "Click here to reset: $resetLink");
        } else {
            // Always show generic message even if email not found
            $message = "If this email exists in our system, a password reset link has been sent.";
        }

        $stmt->close();
    }
}

ob_end_flush();
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Forget Password — SkillShare</title>
    <link rel="stylesheet" href="../assets/css/pages/forget_password.css">
</head>

<body>
    <div class="topbar">
        <span class="brand">
            <a href="../index.php">
                <img src="../assets/images/skillshare-logo.png" alt="SkillShare" style="height:48px;  width: auto; display:block;
  object-fit:contain;">
            </a>
        </span>
    </div>

    <div class="login-wrapper">
        <form class="login-form" method="POST" novalidate>
            <h2>Forget Password</h2>
            <p class="intro">Please enter your email address.<br>You will receive a link to create a new password.</p>

            <?php if (!empty($errors)): ?>
                <div class="msg error">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                    </ul>
                </div>
            <?php elseif ($message): ?>
                <div class="msg success"><?= $message ?></div>
            <?php endif; ?>

            <div class="field">
                <label for="email">Enter your email</label>
                <input id="email" name="email" type="email" placeholder="Your email" required>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Reset Password</button>
            </div>

            <div class="footer-note">
                <a href="login.php">Back to Login</a><br>
                <a href="../index.php">Back to Home Page</a>
            </div>
        </form>
    </div>
</body>

</html>