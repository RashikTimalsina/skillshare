<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// --- Handle Add Skill ---
if (($role === 'admin' || $role === 'teacher') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skill_name = $_POST['skill_name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    $stmt = $conn->prepare("INSERT INTO skills (skill_name, description, categories_category_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $skill_name, $description, $category_id);
    $stmt->execute();

    if ($role === 'teacher') {
        // Link the skill to the teacher in user_skills
        $stmt2 = $conn->prepare("INSERT INTO user_skills (user_id, skill_id, role) VALUES (?, ?, ?)");
        $newSkillId = $conn->insert_id;
        $stmt2->bind_param("iis", $userId, $newSkillId, $role);
        $stmt2->execute();
        $stmt2->close();
    }

    logActivity($conn, $userId, $_SESSION['user_name'], "Added new skill: $skill_name (Category ID: $category_id)");
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

    logActivity($conn, $userId, $_SESSION['user_name'], "Edited skill ID $skill_id: $skill_name (Category ID: $category_id)");
    header("Location: skills.php");
    exit();
}

// --- Handle Delete Skill ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill'])) {
    $skill_id = $_POST['skill_id'];
    $res = $conn->query("SELECT skill_name FROM skills WHERE skill_id=$skill_id");
    $skill = $res->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id=?");
    $stmt->bind_param("i", $skill_id);
    $stmt->execute();

    logActivity($conn, $userId, $_SESSION['user_name'], "Deleted skill ID $skill_id: {$skill['skill_name']}");
    header("Location: skills.php");
    exit();
}

// // --- Search & Sort ---
// $search = $_GET['search'] ?? '';
// $sort = strtoupper($_GET['sort'] ?? 'ASC');
// $sort = $sort === 'DESC' ? 'DESC' : 'ASC';

// $sql = "SELECT skill_id, skill_name
//         FROM skills
//         WHERE CAST(skill_id AS CHAR) LIKE ? OR skill_name LIKE ?
//         ORDER BY skill_id $sort";

// $stmt = $conn->prepare($sql);
// $searchParam = "%$search%";
// $stmt->bind_param("ss", $searchParam, $searchParam);
// $stmt->execute();
// $result = $stmt->get_result();

// $edit_skill_id = $_GET['edit'] ?? null;
// if ($edit_skill_id) {
//     $edit_stmt = $conn->prepare("SELECT * FROM skills WHERE skill_id=?");
//     $edit_stmt->bind_param("i", $edit_skill_id);
//     $edit_stmt->execute();
//     $edit_result = $edit_stmt->get_result()->fetch_assoc();
// }


// --- Fetch skills based on role ---
if ($role === 'admin') {
    $skillsSql = "SELECT s.skill_id, s.skill_name, s.description, c.category_name, s.categories_category_id
                  FROM skills s
                  LEFT JOIN categories c ON s.categories_category_id = c.category_id
                  ORDER BY s.skill_name ASC";
    $skillsResult = $conn->query($skillsSql);
} elseif ($role === 'teacher') {
    $skillsSql = "SELECT s.skill_id, s.skill_name, s.description, c.category_name, s.categories_category_id
                  FROM skills s
                  JOIN user_skills us ON us.skill_id = s.skill_id
                  LEFT JOIN categories c ON s.categories_category_id = c.category_id
                  WHERE us.user_id = ?
                  ORDER BY s.skill_name ASC";
    $stmt = $conn->prepare($skillsSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $skillsResult = $stmt->get_result();
} else { // learner
    $skillsSql = "SELECT s.skill_id, s.skill_name, s.description, c.category_name
                  FROM skills s
                  LEFT JOIN categories c ON s.categories_category_id = c.category_id
                  ORDER BY s.skill_name ASC";
    $skillsResult = $conn->query($skillsSql);
}

// --- Fetch categories for dropdown ---
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/users.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>
    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Skills Management</h2>
            </div>

            <!-- Search & Sort
            <div class="search-sort-container">
                <form method="GET" class="search-sort-form">
                    <input class="search-input" type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 →</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>><- 1</option>
                    </select>
                    <button class="search-btn" type="submit">Apply</button>
                </form>
            </div> -->

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Skill Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($skillsResult->num_rows > 0): ?>
                            <?php while ($row = $skillsResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['skill_id'] ?></td>
                                    <td><?= htmlspecialchars($row['skill_name']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['category_name'] ?? '—') ?></td>
                                    <?php if ($role === 'admin'): ?>
                                        <td>
                                            <a href="?edit=<?= $row['skill_id'] ?>" class="action-link">✏️ Edit</a>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this skill?');">
                                                <input type="hidden" name="skill_id" value="<?= $row['skill_id'] ?>">
                                                <button type="submit" name="delete_skill" class="action-link delete">🗑️ Delete</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $role === 'admin' ? 5 : 4 ?>" style="text-align:center;">No skills found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Skill (Teacher & Admin) -->
            <?php if ($role === 'admin' || $role === 'teacher'): ?>
                <div class="bottom-buttons">
                    <button id="addSkillBtn" class="add-user-btn">+ Add Skill</button>
                </div>
                <div id="addSkillContainer"></div>

                <script>
                    const addBtn = document.getElementById('addSkillBtn');
                    const container = document.getElementById('addSkillContainer');

                    addBtn.addEventListener('click', () => {
                        if (document.getElementById('newSkillForm')) return;

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.id = 'newSkillForm';
                        form.innerHTML = `
                            <table class="users-table" style="margin-top:10px;">
                                <tr>
                                    <td>New</td>
                                    <td><input type="text" name="skill_name" placeholder="Skill Name" required></td>
                                    <td><textarea name="description" placeholder="Description" required></textarea></td>
                                    <td>
                                        <select name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php
                                            $categories->data_seek(0);
                                            while ($cat = $categories->fetch_assoc()) {
                                                echo "<option value='{$cat['category_id']}'>{$cat['category_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" name="add_skill" class="action-link">Save</button>
                                        <button type="button" id="cancelNewSkill" class="action-link delete">Cancel</button>
                                    </td>
                                </tr>
                            </table>
                        `;
                        container.appendChild(form);
                        document.getElementById('cancelNewSkill').addEventListener('click', () => form.remove());
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>