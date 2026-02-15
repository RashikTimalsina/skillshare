<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
require_once('../includes/flash_messages.php'); // <-- Include flash handler

allowRoles(['admin', 'learner', 'teacher']);

require_once '../models/Enrollment.php';
require_once '../models/User.php';
require_once '../models/Skill.php';

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Unknown';

// --- Handle Learner Enrollment ---
if ($role === 'learner' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_skill'])) {
    $skillId = $_POST['skills_skill_id'] ?? null;

    if ($skillId) {
        $res = Enrollment::create($conn, [
            'users_user_id' => $userId,
            'skills_skill_id' => $skillId,
            'status' => 'enrolled'
        ]);

        if ($res) {
            setFlash('enrollments', 'success', 'Successfully enrolled.');
        } else {
            setFlash('enrollments', 'warning', 'You are already enrolled in this skill.');
        }
    } else {
        setFlash('enrollments', 'error', 'Invalid skill selection.');
    }

    header("Location: enrollments.php");
    exit();
}

// --- Handle Teacher Status Update ---
if ($role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $enrollmentId = $_POST['enrollment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($enrollmentId && $status) {
        $stmt = $conn->prepare("
            SELECT e.enrollment_id 
            FROM enrollments e
            JOIN skills s ON e.skills_skill_id = s.skill_id
            JOIN teachers t ON s.skill_id = t.skills_skill_id
            WHERE e.enrollment_id=? AND t.users_user_id=?
        ");
        $stmt->bind_param("ii", $enrollmentId, $userId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            Enrollment::updateStatus($conn, $enrollmentId, $status);
            setFlash('enrollments', 'success', 'Enrollment status updated.');
        } else {
            setFlash('enrollments', 'error', 'Unauthorized or invalid enrollment update attempt.');
        }

        $stmt->close();
    } else {
        setFlash('enrollments', 'error', 'Missing enrollment data.');
    }

    header("Location: enrollments.php");
    exit();
}

// --- Fetch enrollments ---
$enrollments = [];

if ($role === 'admin') {
    $res = $conn->query("
        SELECT e.enrollment_id, e.users_user_id, e.skills_skill_id, e.status, e.enrolled_at
        FROM enrollments e
        ORDER BY e.enrolled_at DESC
    ");
    while ($row = $res->fetch_assoc()) {
        $enrollments[] = (object) $row;
    }
} elseif ($role === 'learner') {
    $enrollments = Enrollment::getByUserId($conn, $userId);
} elseif ($role === 'teacher') {
    $enrollments = Enrollment::getByTeacherSkills($conn, $userId);
}

// --- Fetch skills for learner dropdown ---
$skillsOptions = '';
if ($role === 'learner') {
    $res = $conn->query("
        SELECT s.skill_id, s.skill_name
        FROM skills s
        JOIN teachers t 
            ON t.skills_skill_id = s.skill_id
            AND t.status = 'active'
        ORDER BY s.skill_name ASC
    ");

    while ($s = $res->fetch_assoc()) {
        $skillsOptions .= "<option value='" . htmlspecialchars($s['skill_id']) . "'>"
            . htmlspecialchars($s['skill_name']) . "</option>";
    }
}
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/enrollments.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>
    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>
        <div class="main-content">
            <h2>Enrollments</h2>

            <!-- PAGE-SPECIFIC FLASH MESSAGES -->
            <?php showFlash('enrollments'); ?>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <?php if ($role === 'admin'): ?><th>ID</th><?php endif; ?>
                            <th>Learner</th>
                            <th>Skill</th>
                            <th>Status</th>
                            <th>Enrolled At</th>
                            <?php if ($role === 'teacher'): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollments)): ?>
                            <?php foreach ($enrollments as $enr): ?>
                                <?php
                                $enrObj = is_array($enr) ? (object) $enr : $enr;

                                if ($role === 'learner') {
                                    $user = (object)['user_name' => $userName];
                                } else {
                                    $user = User::getById($conn, $enrObj->users_user_id ?? 0);
                                    if (!$user) $user = (object)['user_name' => 'Unknown'];
                                }

                                $skill = Skill::getById($conn, $enrObj->skills_skill_id ?? 0);
                                if (!$skill) $skill = (object)['skill_name' => 'Unknown'];
                                ?>
                                <tr>
                                    <?php if ($role === 'admin'): ?>
                                        <td><?= htmlspecialchars($enrObj->enrollment_id ?? '') ?></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($user->user_name) ?></td>
                                    <td><?= htmlspecialchars($skill->skill_name) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($enrObj->status ?? '')) ?></td>
                                    <td><?= htmlspecialchars($enrObj->enrolled_at ?? '') ?></td>

                                    <?php if ($role === 'teacher'): ?>
                                        <td>
                                            <?php if (($enrObj->status ?? '') === 'enrolled'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="enrollment_id" value="<?= htmlspecialchars($enrObj->enrollment_id ?? '') ?>">
                                                    <select name="status" required>
                                                        <option value="enrolled" selected>Enrolled</option>
                                                        <option value="completed">Completed</option>
                                                        <option value="dropped">Dropped</option>
                                                    </select>
                                                    <button type="submit" name="update_status" style="background-color: #9959e3; color:aliceblue; margin-top:10px">Update</button>
                                                </form>
                                            <?php else: ?>
                                                <span><?= ucfirst($enrObj->status) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $role === 'teacher' ? 6 : 5 ?>" style="text-align:center;">
                                    No enrollments found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($role === 'learner'): ?>
                <form method="POST" class="learner-enroll-form">
                    <select name="skills_skill_id" required>
                        <option value="">Select Skill to Enroll</option>
                        <?= $skillsOptions ?>
                    </select>
                    <button type="submit" name="enroll_skill">Enroll</button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>