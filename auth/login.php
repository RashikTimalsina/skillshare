<?php
ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }
    if ($password === '') {
        $errors[] = "Password cannot be empty.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id, user_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id,$user_name, $hash, $role);
            $stmt->fetch();

            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_name'] = $user_name; 
                header("Location: ../dashboard/{$_SESSION['user_role']}_dashboard.php");
                exit;
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "Email not found. Please sign up.";
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
    <title>Login — SkillShare</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <div class="topbar">
        <span class="brand">SkillShare</span>
    </div>

    <!-- Wrapper ensures vertical centering like login page -->
    <div class="login-wrapper">
        <form class="login-form" method="POST" novalidate>
            <h2>User Login</h2>

            <?php if (!empty($errors)): ?>
                <div class="msg error">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    placeholder="Email Address" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password"
                    placeholder="Your Password" required>
            </div>

            <div class="forget_password">
                <a href="forget_password.php">Forget Password?</a>
            </div>

            <div class="actions" style="margin-top: 14px;">
                <button type="submit" class="btn">Login</button>
            </div>

            <div class="footer-note">
                Don't have an account? <a href="signup.php">Sign Up</a><br>
                <a href="../index.php">Back to Home Page</a>
            </div>
        </form>
    </div>
</body>

</html>
