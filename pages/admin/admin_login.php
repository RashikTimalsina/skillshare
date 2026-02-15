<?php
// admin_login.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/admin_dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }
    if (!$password) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
    $user = User::getByEmail($conn, $email);

    if ($user && $user->role === 'admin' && password_verify($password, $user->password)) {
        // Successful login
        $_SESSION['admin_id'] = $user->user_id;
        $_SESSION['admin_name'] = $user->user_name;
        logActivity($user->user_id, $user->user_name, "login", "Admin logged in");

        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    } else {
        $errors[] = "Invalid credentials or not an admin.";
    }
}

}
?>

<!-- HTML Part -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $err): ?>
                    <p><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>