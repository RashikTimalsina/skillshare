<?php
// ==========================================
// dashboard/learner_dashboard.php
// Learner-facing dashboard with enrollments,
// progress, certificates, feedback, and activity.
// ==========================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
include('../includes/functions.php');

include('../middlewares/session_check.php');
include('../middlewares/role_check.php');

allowRoles(['learner']);

// Load Models
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Certificate.php';
require_once __DIR__ . '/../models/Feedback.php';
require_once __DIR__ . '/../models/Skill.php';

$learner_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Learner';



// ==============================
// Dashboard metrics
// ==============================
$allEnrollments = Enrollment::getAllByUserId($conn, $learner_id);
$active_enrollments = array_filter($allEnrollments, fn($e) => $e['status'] === 'enrolled');
$completed_enrollments = count(array_filter($allEnrollments, fn($e) => $e['status'] === 'completed'));
$dropped_enrollments = count(array_filter($allEnrollments, fn($e) => $e['status'] === 'dropped'));

$allCertificates = Certificate::getAllByLearner($conn, $learner_id);
$certificates_earned = count($allCertificates);

$allFeedbacks = Feedback::getByUser($conn, $learner_id);

$feedback_given = count(array_filter($allFeedbacks, fn($f) => $f->feedback_given === 'yes'));

// ==============================
// Recent Activities (limit 5)
// ==============================
$recent_activities = getRecentActivities($conn, 'learner', $learner_id, 5);

// ==============================
// Dashboard Cards
// ==============================
$cards = [
    ['title' => 'Active Enrollments', 'count' => count($active_enrollments), 'icon' => 'fa-user-graduate', 'color' => '#1cc88a'],
    ['title' => 'Completed Skills', 'count' => $completed_enrollments, 'icon' => 'fa-check-circle', 'color' => '#36b9cc'],
    ['title' => 'Dropped Skills', 'count' => $dropped_enrollments, 'icon' => 'fa-times-circle', 'color' => '#e74a3b'],
    ['title' => 'Certificates Earned', 'count' => $certificates_earned, 'icon' => 'fa-certificate', 'color' => '#f6c23e'],
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

            <main id="learner-dashboard-main">

                <h3>Welcome, <?= htmlspecialchars($user_name) ?>!</h3>

                <section class="learner-overview-cards">
                    <?php foreach ($cards as $card): ?>
                        <div class="learner-card" style="border-left:5px solid <?= $card['color'] ?>">
                            <i class="fas <?= $card['icon'] ?> card-icon" style="color:<?= $card['color'] ?>"></i>
                            <h3><?= $card['title'] ?></h3>
                            <p><?= $card['count'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- Split Section: Left = Activities, Right = Active Skills Progress -->
                <section class="learner-middle-section">
                    <div class="learner-activities">
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
                                    <?php foreach ($recent_activities as $act): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($act['action'] ?? '') ?></td>
                                            <td><?= !empty($act['created_at']) ? date('d M Y, H:i', strtotime($act['created_at'])) : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No recent activities</p>
                        <?php endif; ?>
                    </div>

                    <!-- <div class="learner-progress">
                        <h3>Active Skills Progress</h3>
                        <?php if (!empty($active_enrollments)): ?>
                            <?php foreach ($active_enrollments as $enroll): ?>
                                <?php
                                $skill = Skill::getById($conn, $enroll['skills_skill_id']);
                                $progress = Enrollment::calculateProgress($conn, $enroll['enrollment_id']);
                                ?>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-title"><?= htmlspecialchars($skill->skill_name ?? 'Unknown Skill') ?></div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" style="width: <?= $progress ?>%;">
                                            <?= $progress ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No active enrollments</p>
                        <?php endif; ?>
                    </div> -->
                </section>

                <h3>Quick Actions</h3>
                <section class="learner-quick-actions">
                    <?php
                    $quickActionColors = ['#1cc88a', '#6f42c1', '#f6c23e', '#e74a3b'];
                    $learnerActions = [
                        ['title' => 'View All Skills', 'icon' => 'fa-book', 'link' => '../pages/skills.php', 'desc' => 'Get a proper view of all skills'],
                        ['title' => 'Enroll in New Skill', 'icon' => 'fa-search', 'link' => '../pages/browse_skills.php', 'desc' => 'Browse and join available skills'],
                        ['title' => 'Give Feedback', 'icon' => 'fa-comments', 'link' => '../pages/feedbacks.php', 'desc' => 'Share your feedback on completed courses'],
                        ['title' => 'My Certificates', 'icon' => 'fa-certificate', 'link' => '../pages/learner_certificates.php', 'desc' => 'View & download certificates'],
                    ];
                    ?>

                    <?php foreach ($learnerActions as $i => $action):
                        $color = $quickActionColors[$i % count($quickActionColors)];
                    ?>
                        <div class="learner-action-card" onclick="window.location.href='<?= $action['link'] ?>'">
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

</body>

</html>