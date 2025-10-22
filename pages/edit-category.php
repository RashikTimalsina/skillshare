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
    header("Location: categories.php");
    exit();
}

$categoryId = $_GET['id'];
$errors = [];
$success = '';

// Fetch existing category
$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Category not found");
}
$category = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!$category_name) {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
        $stmt->bind_param("ssi", $category_name, $description, $categoryId);
        if ($stmt->execute()) {
            $success = "Category updated successfully!";
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
    <h2>Edit Category</h2>

    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="category-form">
        <label>Category Name</label>
        <input type="text" name="category_name" value="<?= htmlspecialchars($category['category_name']) ?>" required>

        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars($category['description']) ?></textarea>

        <button type="submit">Update Category</button>
    </form>
</div>

<?php include('../includes/layouts/footer.php'); ?>