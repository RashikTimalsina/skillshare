<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');

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
    $username = $_POST['user_name'];
    $email = $_POST['email'];
    $user_role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (user_name, email, role, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $user_role, $password);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Added new user: $username ($user_role)");
    header("Location: users.php");
    exit();
}

// --- Handle Edit User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['user_name'];
    $email = $_POST['email'];
    $user_role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET user_name=?, email=?, role=? WHERE user_id=?");
    $stmt->bind_param("sssi", $username, $email, $user_role, $user_id);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Edited user ID $user_id: $username ($user_role)");
    header("Location: users.php");
    exit();
}

// --- Handle Delete User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $delUserRes = $conn->query("SELECT user_name, role FROM users WHERE user_id=$user_id");
    $delUser = $delUserRes->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Deleted user ID $user_id: {$delUser['user_name']} ({$delUser['role']})");
    header("Location: users.php");
    exit();
}

// --- Search & Sort ---
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC');
$sort = $sort === 'DESC' ? 'DESC' : 'ASC';

$sql = "SELECT user_id, user_name, email, role 
        FROM users 
        WHERE CAST(user_id AS CHAR) LIKE ? OR user_name LIKE ?
        ORDER BY user_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

$edit_user_id = $_GET['edit'] ?? null;
if ($edit_user_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $edit_stmt->bind_param("i", $edit_user_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result()->fetch_assoc();
}
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
                                                    <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
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
                    <button class="show-more-btn">Show More</button>
                </div>

                <script>
                    const addBtn = document.getElementById('addUserBtn');
                    const tbody = document.querySelector('.users-table tbody');

                    addBtn.addEventListener('click', () => {
                        // Prevent multiple new rows
                        if (document.getElementById('newUserRow')) return;

                        const newRow = document.createElement('tr');
                        newRow.id = 'newUserRow';

                        newRow.innerHTML = `
                            <td>New</td>
                            <td><input type="text" name="user_name" placeholder="Full Name" required></td>
                            <td><input type="email" name="email" placeholder="Email" required></td>
                            <td>
                                <select name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
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

                        // Cancel button
                        document.getElementById('cancelNewUser').addEventListener('click', () => {
                            newRow.remove();
                        });
                    });

                    // Sidebar toggle
                    const hamburger = document.getElementById('hamburger-btn');
                    const sidebar = document.querySelector('.sidebar');

                    hamburger.addEventListener('click', () => {
                        sidebar.classList.toggle('collapsed');
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>