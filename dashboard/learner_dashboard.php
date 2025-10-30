<?php
// ==========================================
// dashboard/learner_dashboard.php
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';

// Models
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Certificate.php';
require_once __DIR__ . '/../models/Feedback.php';
require_once __DIR__ . '/../models/Activity.php';

include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('learner');

$learner_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown User';

// Log dashboard visit once
if (!isset($_SESSION['dashboard_visited'])) {
    logActivity($conn, $learner_id, $user_name, "Logged in");
    $_SESSION['dashboard_visited'] = true;
}

// ===== Dashboard counts using models =====
$allEnrollments = Enrollment::getAllByUserId($conn, $learner_id);
$skills_enrolled = count($allEnrollments);
$skills_completed = count(array_filter($allEnrollments, fn($e) => $e->status === 'inactive'));

$allCertificates = Certificate::getByLearnerId($conn, $learner_id);
$certificates_earned = count($allCertificates);

$allFeedbacks = Feedback::getByUserId($conn, $learner_id);
$feedback_given = count(array_filter($allFeedbacks, fn($f) => $f->feedback_given === 'yes'));

$progress = $skills_enrolled > 0 ? round(($skills_completed / $skills_enrolled) * 100) : 0;

// Recent activities (last 10)
$recent_activities = Activity::getRecentByUserId($conn, $learner_id, 10);

// Cards array
$cards = [
    ['title' => 'Skills Enrolled', 'count' => $skills_enrolled, 'icon' => 'fa-book', 'color' => '#4e73df'],
    ['title' => 'Skills Completed', 'count' => $skills_completed, 'icon' => 'fa-check-circle', 'color' => '#1cc88a'],
    ['title' => 'Certificates Earned', 'count' => $certificates_earned, 'icon' => 'fa-certificate', 'color' => '#36b9cc'],
    ['title' => 'Feedback Given', 'count' => $feedback_given, 'icon' => 'fa-comments', 'color' => '#f6c23e'],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard | SkillShare</title>
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="layout-wrapper">
        <?php include('../includes/layouts/sidebar.php'); ?>
        <div class="main-content-wrapper">
            <?php include('../includes/layouts/topbar.php'); ?>

            <main class="main-content">
                <!-- DASHBOARD CARDS -->
                <section class="dashboard-overview">
                    <?php foreach ($cards as $card): ?>
                        <div class="card" style="border-left:5px solid <?= $card['color'] ?>">
                            <i class="fas <?= $card['icon'] ?> card-icon" style="font-size:2rem; margin-bottom:10px; color:<?= $card['color'] ?>"></i>
                            <h3><?= $card['title'] ?></h3>
                            <p><?= $card['count'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- MIDDLE SECTION -->
                <!-- <div class="dashboard-middle"> -->
                    <!-- ACTIVE SKILLS -->
                    <!-- <div class="activities-section active-skills">
                        <h>Active Skills</h>
                        <?php if (!empty($allEnrollments)): ?>
                            <?php foreach ($allEnrollments as $enroll):
                                $skillName = htmlspecialchars($enroll->skill_name ?? 'Unknown Skill');
                                $skillProgress = $enroll->status === 'inactive' ? 100 : rand(10, 90); // Replace rand with actual progress if available
                            ?>
                                <div class="skill-card">
                                    <h4><?= $skillName ?></h4>
                                    <div class="skill-progress-bar">
                                        <div class="skill-progress-bar-inner" style="width: <?= $skillProgress ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align:center; color:#777;">No active skills. Enroll in new skills!</p>
                        <?php endif; ?>
                    </div> -->

                    <!-- RECENT ACHIEVEMENTS / FEED -->
                    <!-- <div class="statistics-section recent-achievements">
                        <h2>Recent Achievements</h2>
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $act): ?>
                                <div class="achievement-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?= htmlspecialchars($act->action) ?> — <?= date('d M Y', strtotime($act->created_at)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align:center; color:#777;">No recent achievements</p>
                        <?php endif; ?>
                    </div>
                </div> -->

                <!-- QUICK ACTIONS -->
                <section class="quick-actions">
                    <div class="action-card" onclick="window.location.href='../pages/browse_skills.php'">
                        <i class="fas fa-search fa-2x" style="margin-bottom:10px; color:#4e73df;"></i>
                        <h3>Enroll in New Skill</h3>
                        <p>Browse and join available skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/skills.php'">
                        <i class="fas fa-book fa-2x" style="margin-bottom:10px; color:#1cc88a;"></i>
                        <h3>My Skills</h3>
                        <p>View your enrolled and completed skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/feedbacks.php'">
                        <i class="fas fa-comments fa-2x" style="margin-bottom:10px; color:#f6c23e;"></i>
                        <h3>Give Feedback</h3>
                        <p>Share your feedback on courses</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/certificates.php'">
                        <i class="fas fa-certificate fa-2x" style="margin-bottom:10px; color:#36b9cc;"></i>
                        <h3>My Certificates</h3>
                        <p>View and download earned certificates</p>
                    </div>
                </section>
            </main>

            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>
</body>

</html>