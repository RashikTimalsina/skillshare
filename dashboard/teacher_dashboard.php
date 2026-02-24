<?php
// ==========================================
// dashboard/teacher_dashboard.php
// Teacher-facing dashboard showing offered
// skills, enrollments, feedbacks, and activity.
// ==========================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
include('../includes/functions.php');
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');

allowRoles(['teacher']);

// ==============================
// Include Models
// ==============================
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Certificate.php';
require_once __DIR__ . '/../models/Feedback.php';
require_once __DIR__ . '/../models/Skill.php';
require_once __DIR__ . '/../models/Category.php';

// ==============================
// Teacher Info
// ==============================
$teacher_user_id = $_SESSION['user_id'];
$teacher = User::getById($conn, $teacher_user_id);
$user_name = $teacher->full_name ?? 'Unknown User';


// Fetch Teacher Skills (only active ones)
$teacher_skills = Teacher::getAllByUserId($conn, $teacher_user_id);

// Only keep active skills
$teacher_skills = array_filter($teacher_skills, fn($t) => $t->status === 'active');

// Extract skill IDs
$skill_ids = array_map(fn($t) => $t->skills_skill_id, $teacher_skills);

// Count actual skills offered
$skills_offered = count($teacher_skills);

// ==============================
// Fetch Enrollments
// ==============================
$enrollments = [];
if (!empty($skill_ids)) {
    $placeholders = implode(',', array_fill(0, count($skill_ids), '?'));
    $types = str_repeat('i', count($skill_ids));

    $sql = "SELECT * FROM enrollments WHERE skills_skill_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$skill_ids);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $enrollments[] = new Enrollment($row);
    }
    $stmt->close();
}

$active_enrollments = count(array_filter($enrollments, fn($e) => $e->status === 'enrolled'));
$completed_enrollments = count(array_filter($enrollments, fn($e) => $e->status === 'completed'));

// ==============================
// Fetch Feedbacks
// ==============================
$feedbacks = Feedback::getBySkillIds($conn, $skill_ids);
$pending_feedbacks = count(array_filter($feedbacks, fn($f) => $f->feedback_given === 'no'));

// ==============================
// Fetch Recent Activities (Teacher Only)
// ==============================
$recent_activities = getRecentActivities($conn, 'teacher', $teacher_user_id, 5); // limit 5


// ==============================
// Dashboard Cards Configuration
// ==============================
$cards = [
    ['title' => 'Skills Offered', 'count' => $skills_offered, 'icon' => 'fa-chalkboard-teacher', 'color' => '#6f42c1'],
    ['title' => 'Active Enrollments', 'count' => $active_enrollments, 'icon' => 'fa-user-graduate', 'color' => '#1cc88a'],
    ['title' => 'Completed Enrollments', 'count' => $completed_enrollments, 'icon' => 'fa-check-circle', 'color' => '#36b9cc'],
    ['title' => 'Pending Feedbacks', 'count' => $pending_feedbacks, 'icon' => 'fa-comments', 'color' => '#f6c23e'],
];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | SkillShare</title>
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="layout-wrapper">
        <?php include('../includes/layouts/sidebar.php'); ?>
        <div class="main-content-wrapper">
            <?php include('../includes/layouts/topbar.php'); ?>
            <main id="teacher-dashboard-main">

                <h3>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</h3>

                <section class="teacher-overview-cards">
                    <?php foreach ($cards as $card): ?>
                        <div class="teacher-card" style="border-left:5px solid <?= $card['color'] ?>">
                            <i class="fas <?= $card['icon'] ?> card-icon" style="color:<?= $card['color'] ?>"></i>
                            <h3><?= $card['title'] ?></h3>
                            <p><?= $card['count'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>

                <section class="teacher-middle-section">
                    <div class="teacher-activities">
                        <h3>Recent Activities</h3>
                        <?php if (!empty($recent_activities)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_activities)): ?>
                                        <?php foreach ($recent_activities as $act): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($act['action'] ?? '') ?></td>
                                                <td><?= !empty($act['created_at']) ? date('d M Y, H:i', strtotime($act['created_at'])) : 'N/A' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" style="text-align:center;">No recent activities</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>

                            </table>
                        <?php else: ?>
                            <p>No recent activities</p>
                        <?php endif; ?>
                    </div>


                </section>

                <h3>Quick Actions</h3>
                <section class="teacher-quick-actions">
                    <?php
                    $quickActionColors = ['#6734eb', '#1cc88a', '#52bac8', '#eeaa0c'];
                    $teacherActions = [
                        ['title' => 'Manage Skills', 'icon' => 'fa-chalkboard-teacher', 'link' => '../pages/skills.php', 'desc' => 'View and add skills'],
                        ['title' => 'Feedbacks', 'icon' => 'fa-comments', 'link' => '../pages/feedbacks.php', 'desc' => 'View feedbacks from learners'],
                        ['title' => 'Certificates', 'icon' => 'fa-certificate', 'link' => '../pages/certificates.php', 'desc' => 'Manage and issue course certificates'],
                        ['title' => 'Rewards', 'icon' => 'fa-user-graduate', 'link' => '../pages/teacher_rewards.php', 'desc' => 'View your rewards'],
                    ];
                    ?>

                    <?php foreach ($teacherActions as $i => $action):
                        $color = $quickActionColors[$i % count($quickActionColors)];
                    ?>
                        <div class="teacher-action-card" onclick="window.location.href='<?= $action['link'] ?>'">
                            <i class="fas <?= $action['icon'] ?>" style="color: <?= $color ?>;"></i>
                            <h3><?= $action['title'] ?></h3>
                            <p><?= $action['desc'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>

            </main>
            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>
    <script src="../assets/js/layout.js" defer></script>
</body>

</html>