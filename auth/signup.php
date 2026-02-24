<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $status   = ($role === 'teacher') ? 'inactive' : 'active';
    $agree    = isset($_POST['agree']);

    // ========== VALIDATION ==========
    if (!$username) {
        $errors[] = "Full Name is required.";
    } elseif (!preg_match("/^([A-Z][a-z]+(?:[\s'-][A-Z][a-z]+)*)$/", $username)) {
        $errors[] = "Full Name must start with uppercase letters and contain only letters, spaces, hyphens, or apostrophes.";
    }

    if (!$email) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid Email format.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (!$agree) {
        $errors[] = "You must agree to Terms of Service and Privacy Policy.";
    }

    // ========== DATABASE CHECKS ==========
    if (empty($errors)) {

        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered. Try logging in.";
        } else {

            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Insert into users
            $ins = $conn->prepare("INSERT INTO users (user_name, email, password, role, agreed_terms,status) VALUES (?, ?, ?, ?, ?,?)");
            $agreed = $agree ? 1 : 0;
            $ins->bind_param('ssssss', $username, $email, $hash, $role, $agreed, $status);

            if ($ins->execute()) {

                $newUserId = $conn->insert_id;

                // If teacher, insert into teachers as pending
                if ($role === 'teacher') {
                    $stmtTeacher = $conn->prepare("INSERT INTO teachers (users_user_id, status) VALUES (?, 'inactive')");
                    $stmtTeacher->bind_param("i", $newUserId);
                    $stmtTeacher->execute();
                    $stmtTeacher->close();
                }

                // Set session
                $_SESSION['user_id']   = $newUserId;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_name'] = $username;

                header("Location: login.php?registered=1&role=" . urlencode($role));
                exit();
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
    <link rel="stylesheet" href="../assets/css/pages/signup.css">
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
                    placeholder="Your Password (must be at least 6 characters)" required>
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