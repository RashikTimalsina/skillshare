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

// Fetch categories for dropdown
$catRes = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill_name = trim($_POST['skill_name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];

    if (!$skill_name) $errors[] = "Skill name is required.";
    if (!$category_id) $errors[] = "Category is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO skills (skill_name, description, categories_category_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $skill_name, $description, $category_id);
        if ($stmt->execute()) {
            $success = "Skill added successfully!";
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
    <h2>Add New Skill</h2>

    <?php if ($errors): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="skill-form">
        <label>Skill Name</label>
        <input type="text" name="skill_name" required>

        <label>Description</label>
        <textarea name="description"></textarea>

        <label>Category</label>
        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php while ($cat = $catRes->fetch_assoc()): ?>
                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Add Skill</button>
    </form>
</div>

<?php include('../includes/layouts/footer.php'); ?>