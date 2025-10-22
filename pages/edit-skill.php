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
    header("Location: skills.php");
    exit();
}

$skillId = $_GET['id'];
$errors = [];
$success = '';

// Fetch skill
$stmt = $conn->prepare("SELECT * FROM skills WHERE skill_id=?");
$stmt->bind_param("i", $skillId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Skill not found");
$skill = $result->fetch_assoc();

// Fetch categories
$catRes = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill_name = trim($_POST['skill_name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];

    if (!$skill_name) $errors[] = "Skill name is required.";
    if (!$category_id) $errors[] = "Category is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE skills SET skill_name=?, description=?, categories_category_id=? WHERE skill_id=?");
        $stmt->bind_param("ssii", $skill_name, $description, $category_id, $skillId);
        if ($stmt->execute()) {
            $success = "Skill updated successfully!";
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
    <h2>Edit Skill</h2>

    <?php if ($errors): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="skill-form">
        <label>Skill Name</label>
        <input type="text" name="skill_name" value="<?= htmlspecialchars($skill['skill_name']) ?>" required>

        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars($skill['description']) ?></textarea>

        <label>Category</label>
        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php while ($cat = $catRes->fetch_assoc()): ?>
                <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $skill['categories_category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Update Skill</button>
    </form>
</div>

<?php include('../includes/layouts/footer.php'); ?>