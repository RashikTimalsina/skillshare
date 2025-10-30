<?php
ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';

$message = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        $message = "If this email exists in our system, a password reset link has been sent.";
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
    <link rel="stylesheet" href="../assets/css/forget_password.css">
</head>

<body>
    <div class="topbar"><span class="brand">SkillShare</span></div>

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
                <div class="msg success"><?= htmlspecialchars($message) ?></div>
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