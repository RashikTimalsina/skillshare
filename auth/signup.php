<?php
ob_start();
session_start();
require_once __DIR__ . '/../includes/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = ($_POST['role'] ?? 'learner') === 'teacher' ? 'teacher' : 'learner';
    $agree    = isset($_POST['agree']);

    if ($username === '') $errors[] = "Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    if (!$agree) $errors[] = "You must agree to Terms of Service.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered. Try logging in.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO users (user_name, email, password, role) VALUES (?, ?, ?, ?)");
            $ins->bind_param('ssss', $username, $email, $hash, $role);

            if ($ins->execute()) {
                $_SESSION['user_id'] = $ins->insert_id;
                $_SESSION['user_role'] = strtolower($role);
                header("Location: ../dashboard/{$_SESSION['user_role']}_dashboard.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $ins->close();
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
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up — SkillShare</title>
    <link rel="stylesheet" href="../assets/css/signup.css">
</head>

<body>
    <div class="topbar">
        <span class="brand">SkillShare</span>
    </div>

    <main class="center">
        <form class="signup-form" method="POST" novalidate>
            <h2>User Registration</h2>

            <?php if ($success): ?>
                <div class="msg success">
                    Registration successful. <a href="login.php">Click here to login</a>.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="msg error">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="field">
                    <label for="username">Full Name</label>
                    <input id="username" name="username" type="text"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        placeholder="First and Last name" required>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    placeholder="Email Address" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password"
                    placeholder="Your Password (must be at least 8 characters)" required>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm Password</label>
                <input id="confirm_password" name="confirm_password" type="password"
                    placeholder="Confirm Password" required>
            </div>

            <div class="field">
                <label>Select Role</label>
                <div class="roles">
                    <label>
                        <input type="radio" name="role" value="learner"
                            <?php echo (($_POST['role'] ?? 'learner') === 'learner') ? 'checked' : ''; ?>> Learner
                    </label>
                    <label>
                        <input type="radio" name="role" value="teacher"
                            <?php echo (($_POST['role'] ?? '') === 'teacher') ? 'checked' : ''; ?>> Teacher
                    </label>
                </div>
            </div>

            <div class="field" style="display:flex;align-items:center;gap:12px">
                <input id="agree" name="agree" type="checkbox" <?php if (isset($_POST['agree'])) echo 'checked'; ?>>
                <label for="agree" style="margin:0;font-weight:500">
                    I agree to Terms of Service and Privacy Policy
                </label>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Sign Up</button>
            </div>

            <div class="footer-note">
                Already have an account? <a href="login.php">Login</a><br>
                <a href="../index.php">Back to Home Page</a>
            </div>
        </form>
    </main>
</body>

</html>