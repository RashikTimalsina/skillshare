<!-- =================MY SKILLS (TEACHER)======================= -->

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';
require_once '../models/Skill.php';
require_once('../includes/flash_messages.php');

// Allow only teachers
allowRoles(['teacher']);

$teacherId = $_SESSION['user_id'];
$teacherName = $_SESSION['user_name'] ?? 'Teacher';

// Fetch teacher status from teachers table (optional, keep for account approval)
$stmt = $conn->prepare("SELECT status FROM teachers WHERE users_user_id = ? LIMIT 1");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
$teacherRow = $result->fetch_assoc();
$stmt->close();

$teacherStatus = $teacherRow['status'] ?? 'none';
if ($teacherStatus !== 'active') {
    setFlash('teacher_skills', 'error', 'Your account is not approved by admin yet.');
}

// ---------------------------
// Fetch all teacher skills via user_skills (safe approach)
// ---------------------------
$skills = [];
$stmt = $conn->prepare("
    SELECT s.skill_id, s.skill_name, s.description, s.thumbnail, c.category_name
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.skill_id
    LEFT JOIN categories c ON s.categories_category_id = c.category_id
    WHERE us.user_id = ? AND us.role = 'teacher'
    ORDER BY s.skill_name ASC
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $skills[] = new Skill($row);
}
$stmt->close();

include '../includes/layouts/header.php';
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/users.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>

        <main class="main-content">
            <h2>My Skills</h2>

            <?php showFlash('teacher_skills'); ?>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Skill Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($skills)): ?>
                            <?php foreach ($skills as $index => $skill): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($skill->skill_name) ?></td>
                                    <td><?= htmlspecialchars($skill->category_name ?? '—') ?></td>
                                    <td><?= htmlspecialchars($skill->description ?? '—') ?></td>
                                    <td>Active</td> <!-- user_skills represents active skills -->
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center">You have not added any skills yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <?php include '../includes/layouts/footer.php'; ?>
    </div>
</div>