<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');
require_once __DIR__ . '/../models/Enrollment.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Unknown';

// Fetch users & skills for dropdowns
$usersRes = $conn->query("SELECT user_id, user_name FROM users ORDER BY user_name ASC");
$skillsRes = $conn->query("SELECT skill_id, skill_name FROM skills ORDER BY skill_name ASC");

$usersOptions = '';
if ($usersRes && $usersRes->num_rows > 0) {
    while ($user = $usersRes->fetch_assoc()) {
        $usersOptions .= '<option value="' . $user['user_id'] . '">' . htmlspecialchars($user['user_name']) . '</option>';
    }
}

$skillsOptions = '';
if ($skillsRes && $skillsRes->num_rows > 0) {
    while ($skill = $skillsRes->fetch_assoc()) {
        $skillsOptions .= '<option value="' . $skill['skill_id'] . '">' . htmlspecialchars($skill['skill_name']) . '</option>';
    }
}

// Handle Add Enrollment
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_enrollment'])) {
    $data = [
        'users_user_id' => $_POST['users_user_id'],
        'skills_skill_id' => $_POST['skills_skill_id'],
        'status' => $_POST['status']
    ];
    Enrollment::create($conn, $data, $userId, $userName);
    header("Location: enrollments.php");
    exit();
}

// Handle Edit Enrollment
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_enrollment'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $data = [
        'users_user_id' => $_POST['users_user_id'],
        'skills_skill_id' => $_POST['skills_skill_id'],
        'status' => $_POST['status']
    ];
    Enrollment::update($conn, $enrollment_id, $data, $userId, $userName);
    header("Location: enrollments.php");
    exit();
}

// Handle Delete Enrollment
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_enrollment'])) {
    $enrollment_id = $_POST['enrollment_id'];
    Enrollment::delete($conn, $enrollment_id, $userId, $userName);
    header("Location: enrollments.php");
    exit();
}

// Search & Sort
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC');
$sort = $sort === 'DESC' ? 'DESC' : 'ASC';

$sql = "SELECT e.enrollment_id, e.users_user_id, u.user_name, e.skills_skill_id, s.skill_name, e.status, e.enrolled_at
        FROM enrollments e
        JOIN users u ON e.users_user_id = u.user_id
        JOIN skills s ON e.skills_skill_id = s.skill_id
        WHERE CAST(e.enrollment_id AS CHAR) LIKE ? 
           OR u.user_name LIKE ? 
           OR s.skill_name LIKE ?
        ORDER BY e.enrollment_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// Editing check
$edit_enrollment_id = $_GET['edit'] ?? null;
$edit_enrollment = $edit_enrollment_id ? Enrollment::getById($conn, $edit_enrollment_id) : null;
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/enrollments.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>
    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Enrollments</h2>
            </div>

            <!-- Search & Sort -->
            <div class="search-sort-container">
                <form method="GET" class="search-sort-form">
                    <input class="search-input" type="text" name="search" placeholder="Search by ID or User/Skill" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 →</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>><- 1</option>
                    </select>
                    <button class="search-btn" type="submit">Apply</button>
                </form>
            </div>

            <!-- Enrollments Table -->
            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Skill</th>
                            <th>Status</th>
                            <th>Enrolled At</th>
                            <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <?php if ($role === 'admin' && $edit_enrollment_id == $row['enrollment_id']): ?>
                                        <form method="POST">
                                            <td><?= $row['enrollment_id'] ?></td>
                                            <td>
                                                <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                                <select name="users_user_id" required><?= $usersOptions ?></select>
                                            </td>
                                            <td><select name="skills_skill_id" required><?= $skillsOptions ?></select></td>
                                            <td>
                                                <select name="status" required>
                                                    <?php
                                                    $statuses = ['none', 'enrolled', 'completed', 'dropped'];
                                                    foreach ($statuses as $s) {
                                                        $selected = $s === $row['status'] ? 'selected' : '';
                                                        echo "<option value='$s' $selected>$s</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td><?= $row['enrolled_at'] ?></td>
                                            <td>
                                                <button type="submit" name="edit_enrollment" class="action-link">Save</button>
                                                <a href="enrollments.php" class="action-link delete">Cancel</a>
                                            </td>
                                        </form>
                                    <?php else: ?>
                                        <td><?= $row['enrollment_id'] ?></td>
                                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                                        <td><?= htmlspecialchars($row['skill_name']) ?></td>
                                        <td><?= ucfirst($row['status']) ?></td>
                                        <td><?= $row['enrolled_at'] ?></td>
                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <a href="?edit=<?= $row['enrollment_id'] ?>" class="action-link">✏️ Edit</a>
                                                <form method="POST" class="delete-enrollment-form" style="display:inline-block;">
                                                    <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                                    <button type="submit" name="delete_enrollment" class="action-link delete">🗑️ Delete</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $role === 'admin' ? 6 : 5 ?>" style="text-align:center;">No enrollments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Enrollment Button -->
            <?php if ($role === 'admin'): ?>
                <div class="bottom-buttons">
                    <button id="addEnrollmentBtn" class="add-user-btn" type="button">+ Add Enrollment</button>
                </div>

                <script>
                    const addBtn = document.getElementById('addEnrollmentBtn');
                    const tbody = document.querySelector('.users-table tbody');
                    const usersDropdown = `<?= $usersOptions ?>`;
                    const skillsDropdown = `<?= $skillsOptions ?>`;

                    addBtn.addEventListener('click', () => {
                        if (document.querySelector('.new-enrollment-row')) return;

                        const tr = document.createElement('tr');
                        tr.classList.add('new-enrollment-row');
                        tr.innerHTML = `
                    <form method="POST">
                        <td>New</td>
                        <td><select name="users_user_id" required>${usersDropdown}</select></td>
                        <td><select name="skills_skill_id" required>${skillsDropdown}</select></td>
                        <td>
                            <select name="status" required>
                                <option value="none">none</option>
                                <option value="enrolled">enrolled</option>
                                <option value="completed">completed</option>
                                <option value="dropped">dropped</option>
                            </select>
                        </td>
                        <td>Auto</td>
                        <td>
                            <button type="submit" name="add_enrollment" class="action-link">Save</button>
                            <button type="button" class="action-link cancel">Cancel</button>
                        </td>
                    </form>
                `;
                        tbody.prepend(tr);

                        tr.querySelector('.cancel').addEventListener('click', () => tr.remove());
                    });

                    // Delete confirmation
                    document.querySelectorAll('.delete-enrollment-form').forEach(f => {
                        f.addEventListener('submit', e => {
                            if (!confirm('Are you sure you want to delete this enrollment?')) e.preventDefault();
                        });
                    });
                </script>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>