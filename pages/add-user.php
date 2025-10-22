<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');

// Only admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $status = $_POST['status'];

    // Basic validation
    if (!$full_name || !$email || !$role || !$password) {
        $errors[] = "All fields except status are required.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $email, $hashedPassword, $role, $status);
        if ($stmt->execute()) {
            $success = "User added successfully!";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
}
?>

<?php include('../includes/layouts/header.php'); ?>
<?php include('../includes/layouts/sidebar.php'); ?>
<?php include('../includes/layouts/topbar.php'); ?>

<div class="main-content">
    <h2>Add New User</h2>

    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="user-form">
        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Role</label>
        <select name="role" required>
            <option value="learner">Learner</option>
            <option value="teacher">Teacher</option>
            <option value="admin">Admin</option>
        </select>

        <label>Status</label>
        <select name="status">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <button type="submit">Add User</button>
    </form>
</div>

<?php include('../includes/layouts/footer.php'); ?>