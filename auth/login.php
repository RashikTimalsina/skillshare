<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email.";
    if (empty($password)) $errors[] = "Password cannot be empty.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id, user_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['user_name'];

                logActivity($conn, $_SESSION['user_id'], 'Logged in');

                header("Location: ../dashboard/{$user['role']}_dashboard.php");
                exit();
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
    <link rel="stylesheet" href="../assets/css/pages/login.css">
</head>

<body>
    <header class="topbar">
        <a href="../index.php" class="brand">
            <img src="../assets/images/skillshare-logo.png" alt="SkillShare" style="height:48px; width:auto; display:block; object-fit:contain;">
        </a>
    </header>

    <!-- Wrapper ensures vertical centering like login page -->
    <div class=" login-wrapper">
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
                Need an account? <a href="signup.php">Sign Up</a><br>
                <a href="../index.php">Back to Home Page</a>
            </div>
        </form>
    </div>
</body>

</html>