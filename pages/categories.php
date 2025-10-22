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

// --- Handle Add Category ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $category_name, $description);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// --- Handle Edit Category ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
    $stmt->bind_param("ssi", $category_name, $description, $category_id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// --- Handle Delete Category ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// --- Search & Sort ---
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'ASC';
$sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';

// Search by ID or Category Name, sort only by ID
$sql = "SELECT category_id, category_name, description 
        FROM categories 
        WHERE CAST(category_id AS CHAR) LIKE ? OR category_name LIKE ?
        ORDER BY category_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// --- Determine if editing a category ---
$edit_category_id = $_GET['edit'] ?? null;
if ($edit_category_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=?");
    $edit_stmt->bind_param("i", $edit_category_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result()->fetch_assoc();
}
?>

<?php include('../includes/layouts/header.php'); ?>
<?php include('../includes/layouts/sidebar.php'); ?>
<?php include('../includes/layouts/topbar.php'); ?>

<div class="main-content">
    <h2>Categories Management</h2>

    <!-- Search & Sort -->
    <form method="GET" class="search-sort-form">
        <input type="text" name="search" placeholder="Search by ID or Category Name" value="<?= htmlspecialchars($search) ?>">
        <select name="sort">
            <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>ID Ascending</option>
            <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>>ID Descending</option>
        </select>
        <button type="submit">Apply</button>
    </form>

    <!-- Categories Table -->
    <table class="categories-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Description</th>
                <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['category_id'] ?></td>
                        <td>
                            <?php if ($role === 'admin' && $edit_category_id == $row['category_id']): ?>
                                <form method="POST">
                                    <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
                                    <input type="text" name="category_name" value="<?= htmlspecialchars($row['category_name']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['category_name']) ?>
                                <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($role === 'admin' && $edit_category_id == $row['category_id']): ?>
                                <input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>" required>
                            <?php else: ?>
                                <?= htmlspecialchars($row['description']) ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($role === 'admin'): ?>
                            <td>
                                <?php if ($edit_category_id == $row['category_id']): ?>
                                    <button type="submit" name="edit_category">Save</button>
                                    <a href="categories.php">Cancel</a>
                                    </form>
                                <?php else: ?>
                                    <a href="?edit=<?= $row['category_id'] ?>" title="Edit">✏️</a>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
                                        <button type="submit" name="delete_category">🗑️</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $role === 'admin' ? 4 : 3 ?>" style="text-align:center;">No categories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- + Add Category Button -->
    <?php if ($role === 'admin'): ?>
        <div class="add-category-btn-container">
            <button id="showAddCategoryForm">+ Add Category</button>
        </div>

        <div id="addCategoryForm" style="display:none; margin-top:15px;">
            <form method="POST">
                <input type="text" name="category_name" placeholder="Category Name" required>
                <input type="text" name="description" placeholder="Description" required>
                <button type="submit" name="add_category">Add Category</button>
            </form>
        </div>

        <script>
            const btn = document.getElementById('showAddCategoryForm');
            const form = document.getElementById('addCategoryForm');
            btn.addEventListener('click', () => {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            });
        </script>
    <?php endif; ?>
</div>

<?php include('../includes/layouts/footer.php'); ?>