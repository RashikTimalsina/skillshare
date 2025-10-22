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

// --- Handle Add Skill ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skill_name = $_POST['skill_name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    $stmt = $conn->prepare("INSERT INTO skills (skill_name, description, categories_category_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $skill_name, $description, $category_id);
    $stmt->execute();
    header("Location: skills.php");
    exit();
}

// --- Handle Edit Skill ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_skill'])) {
    $skill_id = $_POST['skill_id'];
    $skill_name = $_POST['skill_name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    $stmt = $conn->prepare("UPDATE skills SET skill_name=?, description=?, categories_category_id=? WHERE skill_id=?");
    $stmt->bind_param("ssii", $skill_name, $description, $category_id, $skill_id);
    $stmt->execute();
    header("Location: skills.php");
    exit();
}

// --- Handle Delete Skill ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill'])) {
    $skill_id = $_POST['skill_id'];
    $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id=?");
    $stmt->bind_param("i", $skill_id);
    $stmt->execute();
    header("Location: skills.php");
    exit();
}

// --- Search & Sort ---
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'ASC';
$sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';

// Search by ID or skill_name, sort only by ID
$sql = "SELECT s.skill_id, s.skill_name, s.description, c.category_name, s.created_at
        FROM skills s
        JOIN categories c ON s.categories_category_id = c.category_id
        WHERE CAST(s.skill_id AS CHAR) LIKE ? OR s.skill_name LIKE ?
        ORDER BY s.skill_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all categories for Add/Edit forms
$catRes = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

// --- Determine if editing a skill ---
$edit_skill_id = $_GET['edit'] ?? null;
if ($edit_skill_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM skills WHERE skill_id=?");
    $edit_stmt->bind_param("i", $edit_skill_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result()->fetch_assoc();
}
?>

<?php include('../includes/layouts/header.php'); ?>
<?php include('../includes/layouts/sidebar.php'); ?>
<?php include('../includes/layouts/topbar.php'); ?>

<div class="main-content">
    <h2>Skills Management</h2>

    <!-- Search & Sort -->
    <form method="GET" class="search-sort-form">
        <input type="text" name="search" placeholder="Search by ID or Skill Name" value="<?= htmlspecialchars($search) ?>">
        <select name="sort">
            <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>ID Ascending</option>
            <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>>ID Descending</option>
        </select>
        <button type="submit">Apply</button>
    </form>

    <!-- Skills Table -->
    <table class="skills-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Skill Name</th>
                <th>Description</th>
                <th>Category</th>
                <th>Created At</th>
                <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['skill_id'] ?></td>
                        <td>
                            <?php if ($role === 'admin' && $edit_skill_id == $row['skill_id']): ?>
                                <form method="POST">
                                    <input type="hidden" name="skill_id" value="<?= $row['skill_id'] ?>">
                                    <input type="text" name="skill_name" value="<?= htmlspecialchars($row['skill_name']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['skill_name']) ?>
                                <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($role === 'admin' && $edit_skill_id == $row['skill_id']): ?>
                                <input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>" required>
                            <?php else: ?>
                                <?= htmlspecialchars($row['description']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($role === 'admin' && $edit_skill_id == $row['skill_id']): ?>
                                <select name="category_id" required>
                                    <?php
                                    $catRes->data_seek(0);
                                    while ($cat = $catRes->fetch_assoc()):
                                    ?>
                                        <option value="<?= $cat['category_id'] ?>" <?= $row['categories_category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($row['category_name']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['created_at'] ?></td>
                        <?php if ($role === 'admin'): ?>
                            <td>
                                <?php if ($edit_skill_id == $row['skill_id']): ?>
                                    <button type="submit" name="edit_skill">Save</button>
                                    <a href="skills.php">Cancel</a>
                                    </form>
                                <?php else: ?>
                                    <a href="?edit=<?= $row['skill_id'] ?>" title="Edit">✏️</a>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="skill_id" value="<?= $row['skill_id'] ?>">
                                        <button type="submit" name="delete_skill">🗑️</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $role === 'admin' ? 6 : 5 ?>" style="text-align:center;">No skills found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- + Add Skill Button -->
    <?php if ($role === 'admin'): ?>
        <div class="add-buttons-container" style="margin-top:15px; display:flex; gap:10px; align-items:center;">
            <!-- Add Skill Button -->
            <button id="showAddSkillForm">+ Add Skill</button>

            <!-- Add Category Button -->
            <a href="categories.php">
                <button type="button">+ Add Category</button>
            </a>
        </div>


        <!-- Add Skill Form -->
        <div id="addSkillForm" style="display:none; margin-top:15px;">
            <form method="POST">
                <input type="text" name="skill_name" placeholder="Skill Name" required>
                <input type="text" name="description" placeholder="Description" required>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php
                    $catRes->data_seek(0);
                    while ($cat = $catRes->fetch_assoc()):
                    ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_skill">Add Skill</button>
            </form>
        </div>

        <script>
            const btn = document.getElementById('showAddSkillForm');
            const form = document.getElementById('addSkillForm');
            btn.addEventListener('click', () => {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            });
        </script>
    <?php endif; ?>
</div>

<?php include('../includes/layouts/footer.php'); ?>