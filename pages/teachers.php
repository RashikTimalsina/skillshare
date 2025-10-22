<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only logged-in users
if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role']; // admin / teacher / learner

// Handle search and sort
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'ASC';
$sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC'; // default ASC

$sql = "SELECT user_id, full_name, email, role FROM users 
        WHERE full_name LIKE ? 
        ORDER BY full_name $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include('../includes/layouts/header.php'); ?>
<?php include('../includes/layouts/sidebar.php'); ?>
<?php include('../includes/layouts/topbar.php'); ?>

<div class="main-content">
    <h2>Users Management</h2>

    <!-- Search & Sort -->
    <form method="GET" class="search-sort-form">
        <input type="text" name="search" placeholder="Search by fullname" value="<?= htmlspecialchars($search) ?>">
        <select name="sort">
            <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>A → Z</option>
            <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>>Z → A</option>
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
                <th>Status</th>
                <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= $row['role'] ?></td>
                        <?php if ($role === 'admin'): ?>
                            <td>
                                <a href="edit-user.php?id=<?= $row['user_id'] ?>" title="Edit">
                                    ✏️
                                </a>
                                <a href="delete-user.php?id=<?= $row['user_id'] ?>" title="Delete" onclick="return confirm('Are you sure?')">
                                    🗑️
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $role === 'admin' ? 6 : 5 ?>">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Add User Button -->
    <?php if ($role === 'admin'): ?>
        <a href="add-user.php" class="add-user-btn">+ Add User</a>
    <?php endif; ?>
</div>

<?php include('../includes/layouts/footer.php'); ?>