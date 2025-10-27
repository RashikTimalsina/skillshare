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
        $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hash, $role);
            $stmt->fetch();

            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = $role;

                // redirect to respective dashboard
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
    <style>
        /* Minimal styling similar to signup page */
        body {
            margin: 0;
            font-family: Inter, sans-serif;
            background: #fff;
            color: #111
        }

        .topbar {
            background: #f6f6f6;
            padding: 14px 28px;
            border-bottom: 1px solid #e6e6e6
        }

        .topbar .brand {
            font-weight: 700
        }

        .center {
            max-width: 760px;
            margin: 40px auto;
            padding: 26px;
            border: 1px solid #ddd;
            background: #fff
        }

        h2 {
            text-align: center;
            margin-top: 0
        }

        form {
            margin-top: 18px
        }

        .field {
            margin: 12px 0
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fafafa
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

        .footer-note {
            text-align: center;
            margin-top: 18px;
            background: #f6f6f6;
            padding: 12px
        }
    </style>
</head>

<body>
    <div class="topbar"><span class="brand">SkillShare</span></div>

    <main class="center">
        <h2>User Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="msg error">
                <ul style="margin:0;padding-left:18px">
                    <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Email Address" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Your Password" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn">Login</button>
            </div>
        </form>

        <div class="footer-note">
            Don't have an account? <a href="signup.php">Sign Up</a><br>
            <a href="../index.php">Back to Home Page</a>
        </div>
    </main>
</body>

</html>