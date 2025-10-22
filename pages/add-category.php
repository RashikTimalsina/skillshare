<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');

// Only admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!$category_name) {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $description);
        if ($stmt->execute()) {
            $success = "Category added successfully!";
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
    <h2>Add New Category</h2>

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
        <input type="text" name="category_name" required>

        <label>Description</label>
        <textarea name="description"></textarea>

        <button type="submit">Add Category</button>
    </form>
</div>

<?php include('../includes/layouts/footer.php'); ?>