<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');

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
    $fullname = $_POST['full_name'];
    $email = $_POST['email'];
    $user_role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, role, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $user_role, $password);
    $stmt->execute();

    // Log activity
    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Added new user: $fullname ($user_role)");

    header("Location: users.php");
    exit();
}

// --- Handle Edit User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $fullname = $_POST['full_name'];
    $email = $_POST['email'];
    $user_role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE user_id=?");
    $stmt->bind_param("sssi", $fullname, $email, $user_role, $user_id);
    $stmt->execute();

    // Log activity
    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Edited user ID $user_id: $fullname ($user_role)");

    header("Location: users.php");
    exit();
}

// --- Handle Delete User ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Fetch user's name and role before deletion
    $delUserRes = $conn->query("SELECT full_name, role FROM users WHERE user_id=$user_id");
    $delUser = $delUserRes->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Log activity
    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Deleted user ID $user_id: {$delUser['full_name']} ({$delUser['role']})");

    header("Location: users.php");
    exit();
}

// --- Search & Sort ---
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'ASC';
$sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';

// Search by ID or Fullname
$sql = "SELECT user_id, full_name, email, role 
        FROM users 
        WHERE CAST(user_id AS CHAR) LIKE ? OR full_name LIKE ?
        ORDER BY user_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// Determine if editing a user
$edit_user_id = $_GET['edit'] ?? null;
if ($edit_user_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $edit_stmt->bind_param("i", $edit_user_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result()->fetch_assoc();
}
?>

<?php include('../includes/layouts/header.php'); ?>
<?php include('../includes/layouts/sidebar.php'); ?>
<?php include('../includes/layouts/topbar.php'); ?>

<div class="main-content">
    <h2>Users Management</h2>

    <!-- Search & Sort -->
    <form method="GET" class="search-sort-form">
        <input type="text" name="search" placeholder="Search by ID or Fullname" value="<?= htmlspecialchars($search) ?>">
        <select name="sort">
            <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 -></option>
            <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>><- 1</option>
        </select>
        <button type="submit">Apply</button>
    </form>

    <!-- Users Table -->
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
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['full_name']) ?>
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
                                <?= $row['role'] ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($role === 'admin'): ?>
                            <td>
                                <?php if ($edit_user_id == $row['user_id']): ?>
                                    <button type="submit" name="edit_user">Save</button>
                                    <a href="users.php">Cancel</a>
                                    </form>
                                <?php else: ?>
                                    <a href="?edit=<?= $row['user_id'] ?>" title="Edit">✏️</a>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <button type="submit" name="delete_user">🗑️</button>
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

    <!-- + Add User Button -->
    <?php if ($role === 'admin'): ?>
        <div class="add-user-btn-container">
            <button id="showAddUserForm">+ Add User</button>
        </div>

        <div id="addUserForm" style="display:none; margin-top:15px;">
            <form method="POST">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="teacher">Teacher</option>
                    <option value="learner">Learner</option>
                </select>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="add_user">Add User</button>
            </form>
        </div>

        <script>
            const btn = document.getElementById('showAddUserForm');
            const form = document.getElementById('addUserForm');
            btn.addEventListener('click', () => {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            });
        </script>
    <?php endif; ?>
</div>

<?php include('../includes/layouts/footer.php'); ?>