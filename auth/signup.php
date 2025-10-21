<?php
ob_start(); // start output buffering
session_start();
require_once __DIR__ . '/../includes/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect + sanitize
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = ($_POST['role'] ?? 'learner') === 'teacher' ? 'teacher' : 'learner';
    $agree    = isset($_POST['agree']);

    // validation
    if ($fullname === '') $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    if (!$agree) $errors[] = "You must agree to Terms of Service.";

    if (empty($errors)) {
        // check email uniqueness
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered. Try logging in.";
            $stmt->close();
        } else {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $ins = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $ins->bind_param('ssss', $fullname, $email, $hash, $role);

            if ($ins->execute()) {
                // Auto-login
                $_SESSION['user_id'] = $ins->insert_id;
                $_SESSION['user_role'] = strtolower($role);

                // redirect to dashboard
                header("Location: ../dashboard/{$_SESSION['user_role']}_dashboard.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $ins->close();
        }
    }
}
ob_end_flush(); // flush output
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Sign Up — SkillShare</title>
    <style>
        /* Simple form styling to match your wireframe layout */
        :root {
            --bg: #fff;
            --muted: #f2f2f2;
            --accent: #2c6f6a;
            --text: #111;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text)
        }

        .topbar {
            background: #f6f6f6;
            padding: 14px 28px;
            border-bottom: 1px solid #e6e6e6;
        }

        .topbar .brand {
            font-weight: 700
        }

        .center {
            max-width: 760px;
            margin: 40px auto;
            border: 1px solid #ddd;
            padding: 26px;
            background: #fff
        }

        h2 {
            margin-top: 0;
            text-align: center
        }

        form {
            display: block;
            margin-top: 18px
        }

        .row {
            display: flex;
            gap: 12px
        }

        .field {
            margin: 12px 0
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fafafa;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600
        }

        .roles {
            display: flex;
            gap: 18px;
            align-items: center;
            margin: 8px 0
        }

        .actions {
            text-align: right
        }

        .btn {
            background: #efefef;
            border: none;
            padding: 12px 18px;
            border-radius: 4px;
            font-weight: 700;
            cursor: pointer
        }

        .msg {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 12px
        }

        .error {
            background: #ffecec;
            border: 1px solid #f5c2c2;
            color: #8a1f1f
        }

        .success {
            background: #e8f7ed;
            border: 1px solid #bfe6c9;
            color: #19692a
        }

        .footer-note {
            text-align: center;
            margin-top: 18px;
            background: #f6f6f6;
            padding: 12px
        }

        @media (max-width:720px) {
            .row {
                flex-direction: column
            }
        }
    </style>
</head>

<body>
    <div class="topbar"><span class="brand">SkillShare</span></div>

    <main class="center">
        <h2>User Registration</h2>

        <?php if ($success): ?>
            <div class="msg success">
                Registration successful. <a href="login.php">Click here to login</a>.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="msg error">
                <ul style="margin:0;padding-left:18px">
                    <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="row">
                <div style="flex:1">
                    <div class="field">
                        <label for="fullname">Full Name</label>
                        <input id="fullname" name="fullname" type="text" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" placeholder="First and Last name" required>
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Email Address" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Your Password (must be at least 8 characters)" required>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm Password</label>
                <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm Password" required>
            </div>

            <div class="field">
                <label>Select Role</label>
                <div class="roles">
                    <label><input type="radio" name="role" value="teacher" <?php echo (($_POST['role'] ?? '') === 'teacher') ? 'checked' : ''; ?>> Teacher</label>
                    <label><input type="radio" name="role" value="learner" <?php echo (($_POST['role'] ?? 'learner') === 'learner') ? 'checked' : ''; ?>> Learner</label>
                </div>
            </div>

            <div class="field" style="display:flex;align-items:center;gap:12px">
                <input id="agree" name="agree" type="checkbox" <?php if (isset($_POST['agree'])) echo 'checked'; ?>>
                <label for="agree" style="margin:0;font-weight:500">I agree to Terms of Service and Privacy Policy</label>
            </div>

            <div class="actions">
                <button type="submit" class="btn">SignUp</button>
            </div>
        </form>

        <div class="footer-note">
            Already have an account? <a href="login.php">Login</a><br>
            <a href="../index.php">Back to Home Page</a>
        </div>
    </main>
</body>

</html>