<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');
require_once __DIR__ . '/../models/User.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role'];

// --- Handle Add User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $data = [
        'user_name' => $_POST['user_name'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
    ];

    $newUserId = User::create($conn, $data);
    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Added new user: {$data['user_name']} ({$data['role']})");
    header("Location: users.php");
    exit();
}

// --- Handle Edit User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $data = [
        'user_name' => $_POST['user_name'],
        'email' => $_POST['email'],
        'role' => $_POST['role']
    ];

    User::update($conn, $user_id, $data);
    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Edited user ID $user_id: {$data['user_name']} ({$data['role']})");
    header("Location: users.php");
    exit();
}

// --- Handle Delete User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $delUser = User::getById($conn, $user_id);

    if ($delUser) {
        User::delete($conn, $user_id);
        logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Deleted user ID $user_id: {$delUser->user_name} ({$delUser->role})");
    }
    header("Location: users.php");
    exit();
}

// --- Search & Sort ---
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC');
$sort = $sort === 'DESC' ? 'DESC' : 'ASC';

$stmt = $conn->prepare("SELECT user_id, user_name, email, role 
                        FROM users 
                        WHERE CAST(user_id AS CHAR) LIKE ? OR user_name LIKE ?
                        ORDER BY user_id $sort");
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

$edit_user_id = $_GET['edit'] ?? null;
$edit_result = $edit_user_id ? User::getById($conn, $edit_user_id) : null;
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/users.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>

    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Users Management</h2>
            </div>

            <!-- Search & Sort -->
            <div class="search-sort-container">
                <form method="GET" class="search-sort-form">
                    <input class="search-input" type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 →</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>><- 1</option>
                    </select>
                    <button class="search-btn" type="submit">Apply</button>
                </form>
            </div>

            <!-- Users Table wrapped in form -->
            <?php if ($role === 'admin'): ?>
                <form method="POST">
                <?php endif; ?>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fullname</th>
                                <th>Email</th>
                                <th>Role</th>
                                <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['user_id'] ?></td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_user_id == $row['user_id']): ?>
                                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                <input type="text" name="user_name" value="<?= htmlspecialchars($row['user_name']) ?>" required>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['user_name']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_user_id == $row['user_id']): ?>
                                                <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['email']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_user_id == $row['user_id']): ?>
                                                <select name="role" required>
                                                    <option value="teacher" <?= $row['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                                    <option value="learner" <?= $row['role'] === 'learner' ? 'selected' : '' ?>>Learner</option>
                                                </select>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['role']) ?>
                                            <?php endif; ?>
                                        </td>

                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <?php if ($edit_user_id == $row['user_id']): ?>
                                                    <button type="submit" name="edit_user">Save</button>
                                                    <a class="action-link" href="users.php">Cancel</a>
                                                <?php else: ?>
                                                    <a class="action-link" href="?edit=<?= $row['user_id'] ?>">✏️ Edit</a>
                                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                        <button class="action-link delete" type="submit" name="delete_user">🗑️ Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $role === 'admin' ? 5 : 4 ?>" style="text-align:center;">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($role === 'admin'): ?>
                </form>
            <?php endif; ?>

            <!-- Bottom Section -->
            <?php if ($role === 'admin'): ?>
                <div class="bottom-buttons">
                    <button id="addUserBtn" class="add-user-btn">+ Add User</button>
                </div>

                <script>
                    const addBtn = document.getElementById('addUserBtn');
                    const tbody = document.querySelector('.users-table tbody');

                    addBtn.addEventListener('click', () => {
                        if (document.getElementById('newUserRow')) return;

                        const newRow = document.createElement('tr');
                        newRow.id = 'newUserRow';

                        newRow.innerHTML = `
        <td>New</td>
        <td><input type="text" name="user_name" placeholder="Full Name" required></td>
        <td><input type="email" name="email" placeholder="Email" required></td>
        <td>
            <select name="role" required>
                <option value="teacher">Teacher</option>
                <option value="learner">Learner</option>
            </select>
        </td>
        <td>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="add_user">Save</button>
            <button type="button" id="cancelNewUser">Cancel</button>
        </td>
    `;
                        tbody.appendChild(newRow);

                        document.getElementById('cancelNewUser').addEventListener('click', () => {
                            newRow.remove();
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>