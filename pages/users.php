<!-- --------USER MANAGEMENT PAGE--------- -->

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';
require_once '../models/User.php';

allowRoles(['admin', 'teacher', 'learner']);

$currentUserRole = $_SESSION['user_role'] ?? 'none';
$currentUserId = $_SESSION['user_id'] ?? 0;

// ================== HANDLE STATUS TOGGLE ==================
if ($currentUserRole === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Toggle Teacher Status
    if (isset($_POST['toggle_status'])) {
        $userId = (int)$_POST['user_id'];
        $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

        $targetUser = User::getById($conn, $userId);
        if ($targetUser && $targetUser->role === 'teacher') {

            // 1️⃣ Update users table
            User::updateStatus($conn, $userId, $newStatus);

            // 2️⃣ Update teachers table as well
            $stmt = $conn->prepare("UPDATE teachers SET status = ? WHERE users_user_id = ?");
            $stmt->bind_param("si", $newStatus, $userId);
            $stmt->execute();
            $stmt->close();

            logActivity($conn, $currentUserId, "Updated teacher with ID {$userId} status to '{$newStatus}'");
        }
        header('Location: users.php');
        exit;
    }


    // Assign Role
    if (isset($_POST['update_role'])) {
        $userId = (int)$_POST['user_id'];
        $role = in_array($_POST['role'], ['teacher', 'learner']) ? $_POST['role'] : 'learner';

        $targetUser = User::getById($conn, $userId);
        if ($targetUser && $targetUser->role === 'none') {
            User::updateRole($conn, $userId, $role);
            logActivity($conn, $currentUserId, "Assigned role '{$role}' to user ID {$userId}");
        }
        header('Location: users.php');
        exit;
    }
}

// ================== FETCH USERS ==================
$users = $currentUserRole === 'admin' ? User::getAll($conn) : [User::getById($conn, $currentUserId)];

// ================== FETCH USERS WITH ROLE FILTER ==================
$filterRole = $_GET['role'] ?? ''; // admin selects role filter
if ($currentUserRole === 'admin') {
    if (in_array($filterRole, ['admin', 'teacher', 'learner'])) {
        $users = User::getAllByRole($conn, $filterRole);
    } else {
        $users = User::getAll($conn); // default: all users
    }
} else {
    $users = [User::getById($conn, $currentUserId)];
}


include '../includes/layouts/header.php';
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/pages/users.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Users Management</h2>
            </div>

            <?php include '../includes/flash_messages.php'; ?>

            <div class="filter-container">
                <?php if ($currentUserRole === 'admin'): ?>
                    <form method="GET" class="role-filter-form">
                        <label for="role">Filter by Role:</label>
                        <select name="role" id="role" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="teacher" <?= $filterRole === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                            <option value="learner" <?= $filterRole === 'learner' ? 'selected' : '' ?>>Learner</option>
                        </select>
                    </form>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user->user_id ?></td>
                                <td><?= htmlspecialchars($user->user_name) ?></td>
                                <td><?= htmlspecialchars($user->email) ?></td>
                                <td>
                                    <?php if ($currentUserRole === 'admin' && $user->role === 'none'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?= $user->user_id ?>">
                                            <select name="role" onchange="this.form.submit()">
                                                <option value="learner">Learner</option>
                                                <option value="teacher">Teacher</option>
                                            </select>
                                            <input type="hidden" name="update_role">
                                        </form>
                                    <?php else: ?>
                                        <?= ucfirst($user->role) ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Status column just shows text -->
                                <td>
                                    <span style="color: <?= $user->status === 'active' ? '#000' : '#333' ?>;">
                                        <?= ucfirst($user->status) ?>
                                    </span>
                                </td>

                                <!-- Actions column now contains the toggle button -->
                                <td>
                                    <?php if ($currentUserRole === 'admin' && $user->role === 'teacher'): ?>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="user_id" value="<?= $user->user_id ?>">
                                            <input type="hidden" name="new_status" value="<?= $user->status === 'active' ? 'inactive' : 'active' ?>">
                                            <button type="submit" name="toggle_status" class="<?= $user->status === 'active' ? 'deactivate-btn' : 'activate-btn' ?>">
                                                <?= $user->status === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/layouts/footer.php'; ?>