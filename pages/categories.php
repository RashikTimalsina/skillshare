<!-- ----------CATEGORIES PAGE--------- -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');
require_once '../includes/flash_messages.php';
include('../middlewares/role_check.php');

allowRoles(['admin', 'teacher', 'learner']);
require_once '../models/Category.php';


if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// --------------------
// Handle Add Category (Admin only)
// --------------------
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $data = [
        'category_name' => trim($_POST['category_name']),
        'description'   => trim($_POST['description'])
    ];
    if (Category::create($conn, $data, $userId, $userName)) {
        setFlash('categories', 'success', 'Category added successfully.');
    } else {
        setFlash('categories', 'error', 'Failed to add category.');
    }

    header("Location: categories.php");
    exit();
}

// --------------------
// Handle Edit Category (Admin only)
// --------------------
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $categoryId = $_POST['category_id'];
    $data = [
        'category_name' => trim($_POST['category_name']),
        'description'   => trim($_POST['description'])
    ];
    if (Category::update($conn, $categoryId, $data, $userId, $userName)) {
        setFlash('categories', 'success', 'Category updated successfully.');
    } else {
        setFlash('categories', 'error', 'Failed to update category.');
    }
    header("Location: categories.php");
    exit();
}

// --------------------
// Handle Delete Category (Admin only)
// --------------------
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $categoryId = $_POST['delete_category'];
    if (Category::delete($conn, $categoryId, $userId, $userName)) {
        setFlash('categories', 'success', 'Category deleted successfully.');
    } else {
        setFlash('categories', 'error', 'Failed to delete category.');
    }

    header("Location: categories.php");
    exit();
    header("Location: categories.php");
    exit();
}

// --------------------
// Fetch, Search & Sort
// --------------------
$allCategories = Category::getAll($conn);

$search = $_GET['search'] ?? '';
$sort   = strtoupper($_GET['sort'] ?? 'ASC');
$sort   = ($sort === 'DESC') ? 'DESC' : 'ASC';

// Filter by search term
if ($search) {
    $searchLower = strtolower($search);
    $allCategories = array_filter($allCategories, function ($cat) use ($searchLower) {
        return str_starts_with(strtolower($cat->category_name), $searchLower)
            || str_starts_with((string)$cat->category_id, $searchLower);
    });
}

// Sort by ID
usort($allCategories, function ($a, $b) use ($sort) {
    return $sort === 'ASC' ? $a->category_id - $b->category_id : $b->category_id - $a->category_id;
});

// --------------------
// Edit Mode
// --------------------
$edit_category_id = $_GET['edit'] ?? null;
$edit_category = $edit_category_id ? Category::getById($conn, $edit_category_id) : null;
?>
<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/categories.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>

    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>

        <div class="main-content">

            <div class="page-header">
                <h2>Categories Management</h2>
            </div>

            <?php showFlash('categories'); ?>
            
            <!-- Search & Sort -->
            <div class="search-sort-container">
                <form method="GET" class="search-sort-form">
                    <input class="search-input" type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>ASC</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>>DESC</option>
                    </select>
                    <button class="search-btn" type="submit">Apply</button>
                </form>
            </div>

            <!-- Categories Table -->
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
                        <?php if (!empty($allCategories)): ?>
                            <?php foreach ($allCategories as $cat): ?>
                                <?php if ($edit_category && $edit_category->category_id == $cat->category_id && $role === 'admin'): ?>
                                    <!-- Inline Edit Row -->
                                    <tr>
                                        <form method="POST">
                                            <td><?= $cat->category_id ?></td>
                                            <td>
                                                <input type="hidden" name="category_id" value="<?= $cat->category_id ?>">
                                                <input type="text" name="category_name" value="<?= htmlspecialchars($cat->category_name) ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" name="description" value="<?= htmlspecialchars($cat->description) ?>" required>
                                            </td>
                                            <td>
                                                <button type="submit" name="edit_category" class="action-link">Save</button>
                                                <a href="categories.php" class="action-link delete">Cancel</a>
                                            </td>
                                        </form>
                                    </tr>
                                <?php else: ?>
                                    <!-- Normal Row -->
                                    <tr>
                                        <td><?= $cat->category_id ?></td>
                                        <td><?= htmlspecialchars($cat->category_name) ?></td>
                                        <td><?= htmlspecialchars($cat->description) ?></td>
                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <a class="action-link" href="?edit=<?= $cat->category_id ?>" style="text-decoration: none">✏️ Edit</a>
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    <button type="submit" name="delete_category" value="<?= $cat->category_id ?>" class="action-link delete" style="text-decoration: none;">🗑️ Delete</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $role === 'admin' ? 4 : 3 ?>" style="text-align:center;">No categories found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Category Button (Admin Only) -->
            <?php if ($role === 'admin'): ?>
                <div class="bottom-buttons">
                    <button id="addCategoryBtn" class="add-user-btn">+ Add Category</button>
                </div>
                <div id="addCategoryContainer"></div>

                <script>
                    const addBtn = document.getElementById('addCategoryBtn');
                    const container = document.getElementById('addCategoryContainer');

                    const categoryOptions = `<?php
                                                $options = '';
                                                foreach ($allCategories as $cat) {
                                                    $options .= '<option value="' . $cat->category_id . '">' . htmlspecialchars($cat->category_name) . '</option>';
                                                }
                                                echo $options;
                                                ?>`;

                    addBtn.addEventListener('click', () => {
                        if (document.getElementById('newCategoryForm')) return;

                        const form = document.createElement('form');
                        form.id = 'newCategoryForm';
                        form.method = 'POST';
                        form.innerHTML = `
                            <table class="categories-table" style="margin-top:10px;">
                                <tr>
                                    <td>New</td>
                                    <td><input type="text" name="category_name" placeholder="Category Name" required></td>
                                    <td><input type="text" name="description" placeholder="Description" required></td>
                                    <td>
                                        <button type="submit" name="add_category" class="action-link">Save</button>
                                        <button type="button" id="cancelNewCategory" class="action-link delete">Cancel</button>
                                    </td>
                                </tr>
                            </table>`;
                        container.appendChild(form);

                        document.getElementById('cancelNewCategory').addEventListener('click', () => form.remove());
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>