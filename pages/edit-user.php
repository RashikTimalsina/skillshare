<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');

// Only admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$userId = $_GET['id'];
$errors = [];
$success = '';

// Fetch existing user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("User not found");
}
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (!$full_name || !$email || !$role) {
        $errors[] = "Full name, email, and role are required.";
    }

    if (empty($errors)) {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, status=?, password=? WHERE user_id=?");
            $stmt->bind_param("sssssi", $full_name, $email, $role, $status, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, status=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $full_name, $email, $role, $status, $userId);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully!";
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
    <h2>Edit User</h2>

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
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Password (leave blank to keep current)</label>
        <input type="password" name="password">

        <label>Role</label>
        <select name="role" required>
            <option value="learner" <?= $user['role'] === 'learner' ? 'selected' : '' ?>>Learner</option>
            <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>

        <label>Status</label>
        <select name="status">
            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>

        <button type="submit">Update User</button>
    </form>
</div>

<?php include('../includes/layouts/footer.php'); ?>