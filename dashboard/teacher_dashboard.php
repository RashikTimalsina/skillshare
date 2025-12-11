<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('teacher');

// ==============================
// Include Models
// ==============================
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Certificate.php';
require_once __DIR__ . '/../models/Feedback.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Skill.php';
require_once __DIR__ . '/../models/Category.php';

// ==============================
// Teacher Info
// ==============================
$teacher_user_id = $_SESSION['user_id'];
$teacher = User::getById($conn, $teacher_user_id);
$user_name = $teacher->full_name ?? 'Unknown User';

// Log dashboard visit once
if (!isset($_SESSION['teacher_dashboard_visited'])) {
    Activity::log($conn, $teacher_user_id, $user_name, "Visited Teacher Dashboard");
    $_SESSION['teacher_dashboard_visited'] = true;
}

// ==============================
// Fetch Teacher Skills & Enrollments
// ==============================
$teacher_skills = Teacher::getAllByUserId($conn, $teacher_user_id);
$skill_ids = array_map(fn($t) => $t->skills_skill_id, $teacher_skills);

// Cards data
$skills_offered = count($teacher_skills);

$enrollments = [];
foreach ($skill_ids as $sid) {
    $enrollments = array_merge($enrollments, Enrollment::getAllBySkillId($conn, $sid));
}
$active_enrollments = count(array_filter($enrollments, fn($e) => $e->status === 'enrolled'));
$completed_enrollments = count(array_filter($enrollments, fn($e) => $e->status === 'completed'));

$feedbacks = [];
foreach ($skill_ids as $sid) {
    $feedbacks = array_merge($feedbacks, Feedback::getBySkillId($conn, $sid));
}
$pending_feedbacks = count(array_filter($feedbacks, fn($f) => $f->feedback_given === 'no'));

// Dashboard cards
$cards = [
    ['title' => 'Skills Offered', 'count' => $skills_offered, 'icon' => 'fa-chalkboard-teacher', 'color' => '#4e73df'],
    ['title' => 'Active Enrollments', 'count' => $active_enrollments, 'icon' => 'fa-user-graduate', 'color' => '#1cc88a'],
    ['title' => 'Completed Enrollments', 'count' => $completed_enrollments, 'icon' => 'fa-check-circle', 'color' => '#36b9cc'],
    ['title' => 'Pending Feedbacks', 'count' => $pending_feedbacks, 'icon' => 'fa-comments', 'color' => '#f6c23e'],
];

// Recent activities
$recent_activities = [];
foreach ($skill_ids as $sid) {
    $recent_activities = array_merge($recent_activities, Activity::getRecentBySkillId($conn, $sid, 10));
}
usort($recent_activities, fn($a, $b) => strtotime($b->created_at) - strtotime($a->created_at));
$recent_activities = array_slice($recent_activities, 0, 10);

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

                <!-- USER GREETING -->
                <h3 style="font-size: 1.5rem; color: #333; font-weight: 200; margin-top: 3.5rem; margin-bottom: 1rem;">
                    Welcome Back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!
                </h3>

                <!-- DASHBOARD CARDS -->
                <section class="teacher-overview-cards">
                    <?php foreach ($cards as $card): ?>
                        <div class="teacher-card" style="border-left:5px solid <?= $card['color'] ?>">
                            <i class="fas <?= $card['icon'] ?> card-icon" style="font-size:2rem; margin-bottom:10px; color:<?= $card['color'] ?>"></i>
                            <h3><?= $card['title'] ?></h3>
                            <p><?= $card['count'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- MIDDLE SECTION: ACTIVITIES + STATS -->
                <section class="teacher-middle-section">
                    <!-- Recent Activities -->
                    <div class="teacher-activities">
                        <h3>Recent Activities</h3>
                        <?php if (!empty($recent_activities)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Action</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $act): ?>
                                        <tr>
                                            <td><?= date('d M Y, H:i', strtotime($act->created_at)) ?></td>
                                            <td><?= htmlspecialchars($act->action) ?></td>
                                            <td><?= htmlspecialchars($act->username ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align:center;">No recent activities</p>
                        <?php endif; ?>
                    </div>

                    <!-- Statistics Section -->
                    <div class="teacher-statistics">
                        <h3>Statistics</h3>
                        <div class="stat-card">Skills Offered: <?= $skills_offered ?></div>
                        <div class="stat-card">Active Enrollments: <?= $active_enrollments ?></div>
                        <div class="stat-card">Completed Enrollments: <?= $completed_enrollments ?></div>
                        <div class="stat-card">Pending Feedbacks: <?= $pending_feedbacks ?></div>
                    </div>
                </section>

                <!-- QUICK ACTIONS -->
                <section class="teacher-quick-actions">
                    <div class="teacher-action-card" onclick="window.location.href='../pages/skills.php'">
                        <i class="fas fa-chalkboard-teacher fa-2x" style="margin-bottom:10px; color:#4e73df;"></i>
                        <h3>My Skills</h3>
                        <p>View your uploaded skills</p>
                    </div>
                    <div class="teacher-action-card" onclick="window.location.href='../pages/enrollments.php'">
                        <i class="fas fa-user-graduate fa-2x" style="margin-bottom:10px; color:#1cc88a;"></i>
                        <h3>Enrollments</h3>
                        <p>Manage learner enrollments</p>
                    </div>
                    <div class="teacher-action-card" onclick="window.location.href='../pages/feedbacks.php'">
                        <i class="fas fa-comments fa-2x" style="margin-bottom:10px; color:#f6c23e;"></i>
                        <h3>Feedbacks</h3>
                        <p>View feedbacks from learners</p>
                    </div>
                    <div class="teacher-action-card" onclick="window.location.href='../pages/certificates.php'">
                        <i class="fas fa-certificate fa-2x" style="margin-bottom:10px; color:#36b9cc;"></i>
                        <h3>Certificates</h3>
                        <p>Manage and issue course certificates</p>
                    </div>
                </section>

            </main>
            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>

    <script src="../assets/js/layout.js" defer></script>
</body>

</html>