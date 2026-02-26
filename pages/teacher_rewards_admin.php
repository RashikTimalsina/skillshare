<!-- =================TEACHER REWARDS PAGE (ADMIN)======================= -->
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../middlewares/auth_check.php';
require_once __DIR__ . '/../middlewares/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/TeacherReward.php';
require_once __DIR__ . '/../includes/flash_messages.php';

allowRoles(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $points    = (int)($_POST['points'] ?? 0);
    $reason    = trim($_POST['reason'] ?? '');

    if ($teacherId <= 0 || $points <= 0 || empty($reason)) {
        setFlash('teacher_rewards_admin', 'error', 'Invalid reward data.');
    } else {
        // Award reward
        TeacherReward::award($conn, $teacherId, $points, $reason, $_SESSION['user_id']);

        // --- Fetch teacher + skill details ---
        $stmt = $conn->prepare("
        SELECT u.user_id, u.user_name, s.skill_name
        FROM teachers t
        JOIN users u ON u.user_id = t.users_user_id
        LEFT JOIN skills s ON s.skill_id = t.skills_skill_id
        WHERE t.id = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($data) {
            $teacherUserId = $data['user_id'];
            $teacherName   = $data['user_name'] ?? 'Unknown Teacher';
            $skillName     = $data['skill_name'] ?? 'Unknown Skill';

            // --- 1. Admin Activity ---
            logActivity(
                $conn,
                $_SESSION['user_id'], // admin
                "Assigned {$points} reward points to teacher: '{$teacherName}' skill: '{$skillName}'"
            );

            // --- 2. Teacher Activity ---
            logActivity(
                $conn,
                $teacherUserId,
                "Earned {$points} reward points for skill: '{$skillName}'"
            );
        }

        setFlash('teacher_rewards_admin', 'success', 'Reward awarded successfully.');
    }

    header("Location: ./teacher_rewards_admin.php");
    exit;
}

// Fetch active teachers with teacher IDs
$teachers = $conn->query("
    SELECT DISTINCT t.id, u.user_name, u.email
    FROM teachers t
    JOIN users u ON u.user_id = t.users_user_id
    WHERE t.status = 'active'
    ORDER BY u.user_name ASC
")->fetch_all(MYSQLI_ASSOC);

$rewardHistory = TeacherReward::getAll($conn);

require_once __DIR__ . '/../includes/layouts/header.php';
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/teacher_rewards_admin.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Teacher Rewards</h2>
                <!-- <p>Reward teachers for performance, contributions, and achievements.</p> -->
            </div>

            <!-- Display flash messages -->
            <?php showFlash('teacher_rewards_admin'); ?>

            <div class="reward-form-card">
                <h3>Award New Reward</h3>
                <form method="POST">
                    <label for="teacher_id">Select Teacher:</label>
                    <select name="teacher_id" id="teacher_id" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['user_name']) ?> (<?= $t['email'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="points">Points:</label>
                    <input type="number" name="points" id="points" placeholder="Enter points" min="1" required>

                    <label for="reason">Reason:</label>
                    <textarea name="reason" id="reason" placeholder="Explain why the teacher is awarded" required></textarea>

                    <button type="submit">Award Reward</button>
                </form>
            </div>

            <div class="table-container">
                <h3>Reward History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Points</th>
                            <th>Reason</th>
                            <th>Awarded On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rewardHistory)): ?>
                            <?php foreach ($rewardHistory as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['teacher_name']) ?></td>
                                    <td><?= (int)$r['points'] ?></td>
                                    <td><?= htmlspecialchars($r['reason']) ?></td>
                                    <td><?= date('d M Y', strtotime($r['awarded_date'])) ?></td>
                                    <td><?= ucfirst($r['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No rewards have been awarded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/layouts/footer.php'; ?>