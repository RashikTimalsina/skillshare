<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Access control ---
if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role'];

// --- CREATE (Add Category) ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!empty($category_name) && !empty($description)) {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $description);
        $stmt->execute();

        logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Added new category: $category_name");
        header("Location: categories.php");
        exit();
    }
}

// --- UPDATE (Edit Category) ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
    $stmt->bind_param("ssi", $category_name, $description, $category_id);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Edited category ID $category_id: $category_name");
    header("Location: categories.php");
    exit();
}

// --- DELETE (Remove Category) ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = $_POST['delete_category']; // Using button value

    $delCatRes = $conn->query("SELECT category_name FROM categories WHERE category_id=$category_id");
    $delCat = $delCatRes->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Deleted category ID $category_id: {$delCat['category_name']}");
    header("Location: categories.php");
    exit();
}

// --- SEARCH & SORT ---
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC');
$sort = ($sort === 'DESC') ? 'DESC' : 'ASC';

$sql = "SELECT category_id, category_name, description 
        FROM categories 
        WHERE CAST(category_id AS CHAR) LIKE ? OR category_name LIKE ?
        ORDER BY category_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// --- For edit mode ---
$edit_category_id = $_GET['edit'] ?? null;
if ($edit_category_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=?");
    $edit_stmt->bind_param("i", $edit_category_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result()->fetch_assoc();
}
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/categories.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>

    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Categories Management</h2>
            </div>

            <!-- 🔍 Search & Sort -->
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

            <!-- 📋 Categories Table -->
            <?php if ($role === 'admin'): ?>
                <form method="POST">
                <?php endif; ?>
                <div class="table-container">
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
                                            <?php if ($edit_category_id == $row['category_id']): ?>
                                                <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
                                                <input type="text" name="category_name" value="<?= htmlspecialchars($row['category_name']) ?>" required>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['category_name']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($edit_category_id == $row['category_id']): ?>
                                                <input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>" required>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['description']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <?php if ($edit_category_id == $row['category_id']): ?>
                                                    <button type="submit" name="edit_category">Save</button>
                                                    <a class="action-link" href="categories.php">Cancel</a>
                                                <?php else: ?>
                                                    <a class="action-link" href="?edit=<?= $row['category_id'] ?>">✏️ Edit</a>
                                                    <button type="submit" name="delete_category" value="<?= $row['category_id'] ?>" class="action-link delete" onclick="return confirm('Are you sure?')">🗑️ Delete</button>
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
                </div>
                <?php if ($role === 'admin'): ?>
                </form>
            <?php endif; ?>

            <!-- ➕ Bottom Buttons -->
            <?php if ($role === 'admin'): ?>
                <div class="bottom-buttons">
                    <button id="addCategoryBtn" class="add-user-btn">+ Add Category</button>
                </div>

                <script>
                    const addBtn = document.getElementById('addCategoryBtn');
                    const tbody = document.querySelector('.categories-table tbody');

                    addBtn.addEventListener('click', () => {
                        if (document.getElementById('newCategoryRow')) return;

                        const newRow = document.createElement('tr');
                        newRow.id = 'newCategoryRow';

                        newRow.innerHTML = `
                    <td>New</td>
                    <td><input type="text" name="category_name" placeholder="Category Name" required></td>
                    <td><input type="text" name="description" placeholder="Description" required></td>
                    <td>
                        <button type="submit" name="add_category">Save</button>
                        <button type="button" id="cancelNewCategory">Cancel</button>
                    </td>
                `;

                        tbody.appendChild(newRow);

                        document.getElementById('cancelNewCategory').addEventListener('click', () => {
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