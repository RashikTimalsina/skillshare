<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';
require_once '../models/Skill.php';
require_once '../includes/flash_messages.php'; // <-- Include flash handler

allowRoles(['admin', 'teacher', 'learner']);
$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';

// --- Fetch teacher status if role is teacher ---
$teacherStatus = null;
if ($role === 'teacher') {
    $stmt = $conn->prepare("
    SELECT status 
    FROM teachers 
    WHERE users_user_id=? 
    ORDER BY FIELD(status, 'active','inactive','none') ASC
    LIMIT 1
");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $teacherStatus = $teacher['status'] ?? null;
}

// --- Handle "Request Approval" button ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_approval'])) {
    setFlash('skills', 'success', "Your approval request is already visible to admins. Please wait for approval.");
    logActivity($conn, $userId, "Requested account approval to create skills");
    header("Location: skills.php");
    exit();
}

// --- Handle POST for teachers with active status ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'teacher' && $teacherStatus === 'active') {
    // ADD SKILL
    if (isset($_POST['add_skill'])) {
        $skillName = trim($_POST['skill_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = $_POST['categories_category_id'] ?? null;

        if ($skillName === '' || $description === '' || empty($categoryId)) {
            setFlash('skills', 'error', "Please fill in all required fields.");
            header("Location: skills.php");
            exit();
        }

        try {
            $data = [
                'skill_name' => $skillName,
                'description' => $description,
                'categories_category_id' => $categoryId
            ];
            $newSkillId = Skill::create($conn, $data, $userId, $_FILES);
            if ($newSkillId) {
                setFlash('skills', 'success', "Skill '{$skillName}' added successfully!");
                logActivity($conn, $userId, "Added new skill '{$skillName}'");
            } else {
                setFlash('skills', 'success', "Skill '{$skillName}' already exists!");
            }
        } catch (Throwable $e) {
            setFlash('skills', 'error', "Error adding skill: " . $e->getMessage());
        }

        header("Location: skills.php");
        exit();
    }

    // EDIT SKILL
    if (isset($_POST['edit_skill'])) {
        $skillId = (int)($_POST['skill_id'] ?? 0);
        $skillName = trim($_POST['skill_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = $_POST['categories_category_id'] ?? null;

        if (!Skill::isOwnedByUser($conn, $skillId, $userId)) {
            setFlash('skills', 'error', "Unauthorized action!");
        } else {
            $data = [
                'skill_name' => $skillName,
                'description' => $description,
                'categories_category_id' => $categoryId
            ];
            try {
                $updated = Skill::update($conn, $skillId, $data, $_FILES);
                if ($updated) {
                    setFlash('skills', 'success', "Skill updated successfully!");
                    logActivity($conn, $userId, "Updated skill '{$skillName}'");
                } else {
                    setFlash('skills', 'success', "Cannot update, duplicate name!");
                }
            } catch (Throwable $e) {
                setFlash('skills', 'error', "Error updating skill: " . $e->getMessage());
            }
        }

        header("Location: skills.php");
        exit();
    }

    // DELETE SKILL
    if (isset($_POST['delete_skill'])) {
        $skillId = (int)($_POST['skill_id'] ?? 0);

        if (!Skill::isOwnedByUser($conn, $skillId, $userId)) {
            setFlash('skills', 'error', "Unauthorized action!");
        } else {

            // Fetch skill info before deletion
            $skill = Skill::getById($conn, $skillId);

            $deleted = Skill::delete($conn, $skillId);
            if ($deleted) {
                setFlash('skills', 'success', "Skill deleted successfully!");
                if ($skill) {
                    logActivity($conn, $userId, "Deleted skill '{$skill->skill_name}'");
                }
            } else {
                setFlash('skills', 'error', "Failed to delete!");
            }
        }

        header("Location: skills.php");
        exit();
    }
}

// --- Fetch Skills ---
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$skills = Skill::getAllWithCategory($conn, $role, $userId, $search, $sort);

// Fetch categories
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");

// --- Server-side Edit Mode ---
$edit_skill_id = $_GET['edit'] ?? null;
$edit_skill = $edit_skill_id ? Skill::getById($conn, (int)$edit_skill_id) : null;

include('../includes/layouts/header.php');
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/users.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>
    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>
        <div class="main-content">
            <h2>Skills Management</h2>

            <!-- Page-specific flash messages -->
            <?php showFlash('skills'); ?>

            <!-- Search & Sort -->
            <div class="search-sort-container">
                <form method="GET" class="search-sort-form">
                    <input class="search-input" type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 →</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>>← 1</option>
                    </select>
                    <button type="submit" class="search-btn">Apply</button>
                </form>
            </div>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <?php if ($role === 'admin'): ?><th>ID</th><?php endif; ?>
                            <th>Skill Name</th>
                            <th>Offered By</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Thumbnail</th>
                            <?php if ($role === 'teacher') echo '<th>Actions</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $catsArray = [];
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()) $catsArray[$cat['category_id']] = $cat['category_name'];
                        ?>
                        <?php foreach ($skills as $skill): ?>
                            <tr id="skill-<?= $skill->skill_id ?>">
                                <?php if ($edit_skill && $edit_skill->skill_id == $skill->skill_id && $role === 'teacher'): ?>
                                    <!-- Inline Edit Row -->
                                    <form method="POST" enctype="multipart/form-data">
                                        <td><?= $skill->skill_id ?></td>
                                        <td>
                                            <input type="hidden" name="skill_id" value="<?= $skill->skill_id ?>">
                                            <input type="text" name="skill_name" value="<?= htmlspecialchars($skill->skill_name) ?>" required>
                                        </td>
                                        <td><input type="text" value="<?= htmlspecialchars($skill->offered_by_name ?? '') ?>" disabled></td>
                                        <td><textarea name="description" required><?= htmlspecialchars($skill->description) ?></textarea></td>
                                        <td>
                                            <select name="categories_category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($catsArray as $id => $name): ?>
                                                    <option value="<?= $id ?>" <?= $skill->categories_category_id == $id ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <?php if (!empty($skill->thumbnail)): ?>
                                                    <img src="../<?= $skill->thumbnail ?>" style="width:50px;height:50px;object-fit:cover;">
                                                <?php else: ?>
                                                    <div style="width:50px;height:50px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:12px;color:#555;">No Image</div>
                                                <?php endif; ?>
                                                <input type="file" name="thumbnail" accept="image/*" style="margin:0;">
                                            </div>
                                        </td>
                                        <td>
                                            <button type="submit" name="edit_skill" class="action-link">Save</button>
                                            <a href="skills.php" class="action-link delete">Cancel</a>
                                        </td>
                                    </form>
                                <?php else: ?>
                                    <!-- Normal Row -->
                                    <?php if ($role === 'admin'): ?>
                                        <td><?= htmlspecialchars($skill->skill_id) ?></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($skill->skill_name) ?></td>
                                    <td><?= htmlspecialchars($skill->offered_by_name ?? '—') ?></td>
                                    <td><?= htmlspecialchars($skill->description) ?></td>
                                    <td><?= htmlspecialchars($skill->category_name ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($skill->thumbnail) && file_exists("../" . $skill->thumbnail)): ?>
                                            <img src="../<?= $skill->thumbnail ?>" alt="Thumbnail" style="width:50px;height:50px;object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:12px;color:#555;">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <?php if ($role === 'teacher' && $teacherStatus === 'active'): ?>
                                        <td>
                                            <?php if ($skill->owned_by_user): ?>
                                                <a href="?edit=<?= $skill->skill_id ?>#skill-<?= $skill->skill_id ?>" class="action-link" style="text-decoration:none">✏️ Edit</a>
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                                    <input type="hidden" name="skill_id" value="<?= $skill->skill_id ?>">
                                                    <button type="submit" name="delete_skill" class="action-link delete" style="text-decoration:none">🗑️ Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($role === 'teacher' && $teacherStatus !== 'active'): ?>
                <!-- Request Approval Button for inactive teachers -->
                <div style="margin:20px 0;padding:10px;">
                    <form method="POST">
                        <button type="submit" name="request_approval" class="add-user-btn">
                            Request Account Approval
                        </button>
                    </form>
                    <p style="color:#555; font-size:14px; padding: 10px 10px">
                        Your account is not active. You cannot create skills until approved by an admin.
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($role === 'teacher' && $teacherStatus === 'active'): ?>
                <div class="bottom-buttons"><button id="addSkillBtn" class="add-user-btn">+ Add Skill</button></div>
                <div id="addSkillContainer"></div>

                <script>
                    const catsArray = <?= json_encode($catsArray) ?>;
                    const addBtn = document.getElementById('addSkillBtn');
                    const container = document.getElementById('addSkillContainer');

                    addBtn.addEventListener('click', () => {
                        if (document.getElementById('newSkillForm')) return;

                        let options = '<option value="">Select Category</option>';
                        Object.entries(catsArray).forEach(([id, name]) => options += `<option value="${id}">${name}</option>`);

                        const form = document.createElement('form');
                        form.id = 'newSkillForm';
                        form.method = 'POST';
                        form.enctype = 'multipart/form-data';
                        form.style.marginTop = '10px';
                        form.innerHTML = `
                            <table class="users-table">
                                <tr>
                                    <td>New</td>
                                    <td><input type="text" name="skill_name" required></td>
                                    <td><input type="text" value="<?= htmlspecialchars($userName) ?>" disabled></td>
                                    <td><textarea name="description" required></textarea></td>
                                    <td><select name="categories_category_id" required>${options}</select></td>
                                    <td><input type="file" name="thumbnail" accept="image/*" required></td>
                                    <td>
                                        <button type="submit" name="add_skill">Save</button>
                                        <button type="button" id="cancelNewSkill" class="action-link delete">Cancel</button>
                                    </td>
                                </tr>
                            </table>
                        `;
                        container.appendChild(form);

                        form.querySelector('#cancelNewSkill').addEventListener('click', () => form.remove());
                    });

                    // Smooth scroll to edited skill
                    document.addEventListener('DOMContentLoaded', () => {
                        if (window.location.hash) {
                            const el = document.querySelector(window.location.hash);
                            if (el) el.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>