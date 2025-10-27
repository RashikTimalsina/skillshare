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

// --- Fetch users & skills early (for dropdowns) ---
$usersRes = $conn->query("SELECT user_id, user_name FROM users ORDER BY user_name ASC");
$skillsRes = $conn->query("SELECT skill_id, skill_name FROM skills ORDER BY skill_name ASC");

// --- Prepare dropdown options ---
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

// --- Handle Add Enrollment ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_enrollment'])) {
    $user_id = $_POST['users_user_id'];
    $skill_id = $_POST['skills_skill_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO enrollments (users_user_id, skills_skill_id, status, enrolled_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $user_id, $skill_id, $status);
    $stmt->execute();

    // Log action (admin performing it)
    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['user_name'] ?? 'Unknown',
        "Added enrollment for user ID $user_id (Skill ID: $skill_id, Status: $status)"
    );

    header("Location: enrollments.php");
    exit();
}

// --- Handle Edit Enrollment ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_enrollment'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $user_id = $_POST['users_user_id'];
    $skill_id = $_POST['skills_skill_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE enrollments SET users_user_id=?, skills_skill_id=?, status=? WHERE enrollment_id=?");
    $stmt->bind_param("iisi", $user_id, $skill_id, $status, $enrollment_id);
    $stmt->execute();

    // Log action (admin performing it)
    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['user_name'] ?? 'Unknown',
        "Edited enrollment ID $enrollment_id → User ID: $user_id, Skill ID: $skill_id, Status: $status"
    );

    header("Location: enrollments.php");
    exit();
}

// --- Handle Delete Enrollment ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_enrollment'])) {
    $enrollment_id = $_POST['enrollment_id'];

    $stmt = $conn->prepare("DELETE FROM enrollments WHERE enrollment_id=?");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();

    // Log action (admin performing it)
    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['user_name'] ?? 'Unknown',
        "Deleted enrollment ID $enrollment_id"
    );

    header("Location: enrollments.php");
    exit();
}


// --- Search & Sort ---
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

// --- Check if editing ---
$edit_enrollment_id = $_GET['edit'] ?? null;
if ($edit_enrollment_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM enrollments WHERE enrollment_id=?");
    $edit_stmt->bind_param("i", $edit_enrollment_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result()->fetch_assoc();
}
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
                    <input class="search-input" type="text" name="search" placeholder="Search by ID or U/S ID" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 →</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>><- 1</option>
                    </select>
                    <button class="search-btn" type="submit">Apply</button>
                </form>
            </div>

            <!-- Enrollments Table -->
            <form method="POST">
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
                                        <td><?= $row['enrollment_id'] ?></td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_enrollment_id == $row['enrollment_id']): ?>
                                                <select name="users_user_id" required><?= $usersOptions ?></select>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['user_name']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_enrollment_id == $row['enrollment_id']): ?>
                                                <select name="skills_skill_id" required><?= $skillsOptions ?></select>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['skill_name']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_enrollment_id == $row['enrollment_id']): ?>
                                                <select name="status" required>
                                                    <?php
                                                    $statuses = ['none', 'enrolled', 'completed', 'dropped'];
                                                    foreach ($statuses as $s) {
                                                        $selected = $s === $row['status'] ? 'selected' : '';
                                                        echo "<option value='$s' $selected>$s</option>";
                                                    }
                                                    ?>
                                                </select>
                                            <?php else: ?>
                                                <?= ucfirst($row['status']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $row['enrolled_at'] ?></td>
                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <?php if ($edit_enrollment_id == $row['enrollment_id']): ?>
                                                    <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                                    <button type="submit" name="edit_enrollment" class="action-link">Save</button>
                                                    <a href="enrollments.php" class="action-link delete">Cancel</a>
                                                <?php else: ?>
                                                    <a href="?edit=<?= $row['enrollment_id'] ?>" class="action-link">✏️ Edit</a>
                                                    <form method="POST" style="display:inline-block;">
                                                        <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                                        <button type="submit" name="delete_enrollment" class="action-link delete">🗑️ Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
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
            </form>

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
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
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
                        `;
                        tbody.appendChild(newRow);
                        newRow.querySelector('.cancel').addEventListener('click', () => newRow.remove());
                    });

                    const hamburger = document.getElementById('hamburger-btn');
                    const sidebar = document.querySelector('.sidebar');
                    hamburger.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
                </script>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>