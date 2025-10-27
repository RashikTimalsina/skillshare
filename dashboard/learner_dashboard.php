<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('learner');

$learner_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Unknown User';


if (!isset($_SESSION['dashboard_visited'])) {
    logActivity($conn, $learner_id, $user_name, "Logged in ");
    $_SESSION['dashboard_visited'] = true;
}


// Dashboard cards
$cards = [
    'Skills Enrolled' => "SELECT COUNT(DISTINCT skills_skill_id) AS total FROM enrollments WHERE users_user_id='$learner_id'",
    'Skills Completed' => "SELECT COUNT(DISTINCT skills_skill_id) AS total FROM enrollments WHERE users_user_id='$learner_id' AND status='inactive'",
    'Certificates Earned' => "SELECT COUNT(*) AS total FROM certificates WHERE learner_id='$learner_id' AND status='active'",
    'Feedback Given' => "SELECT COUNT(*) AS total FROM feedback WHERE users_user_id='$learner_id' AND feedback_given='yes'",
];

// Recent activities (last 10)
$activities_query = "
    SELECT created_at, action
    FROM activities
    WHERE user_id='$learner_id'
    ORDER BY created_at DESC
    LIMIT 10
";
$activities_res = mysqli_query($conn, $activities_query);
$recent_activities = [];
if ($activities_res && mysqli_num_rows($activities_res) > 0) {
    while ($row = mysqli_fetch_assoc($activities_res)) {
        $recent_activities[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard | SkillShare</title>
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="layout-wrapper">
        <?php include('../includes/layouts/sidebar.php'); ?>
        <div class="main-content-wrapper">
            <?php include('../includes/layouts/topbar.php'); ?>
            <main class="main-content">
                <!-- DASHBOARD CARDS -->
                <section class="dashboard-overview">
                    <?php
                    foreach ($cards as $title => $query) {
                        $res = mysqli_query($conn, $query);
                        $count = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res)['total'] : 0;
                        echo "<div class='card'><h3>$title</h3><p>$count</p></div>";
                    }
                    ?>
                </section>

                <!-- RECENT ACTIVITIES -->
                <section class="recent-activities">
                    <h2>Recent Activities</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($recent_activities)) {
                                foreach ($recent_activities as $i => $act) {
                                    $hide = $i >= 5 ? 'display:none;' : '';
                                    echo "<tr class='activity-row' style='$hide'>
                                        <td>" . date('d M Y, H:i', strtotime($act['created_at'])) . "</td>
                                        <td>" . htmlspecialchars($act['action']) . "</td>
                                      </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2' style='text-align:center;'>No recent activities</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div style="text-align:center;">
                        <button id="show-more-btn" style="display:<?= count($recent_activities) > 5 ? 'inline-block' : 'none' ?>;">Show More</button>
                    </div>
                </section>

                <!-- QUICK ACTIONS -->
                <section class="quick-actions">
                    <div class="action-card" onclick="window.location.href='../pages/browse_skills.php'">
                        <h3>Enroll in New Skill</h3>
                        <p>Browse and join available skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/skills.php'">
                        <h3>My Skills</h3>
                        <p>View your enrolled and completed skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/feedbacks.php'">
                        <h3>Give Feedback</h3>
                        <p>Share feedback on your learning experiences</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/certificates.php'">
                        <h3>My Certificates</h3>
                        <p>View and download earned certificates</p>
                    </div>
                </section>
            </main>
            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>

    <script src="../assets/js/layout.js" defer></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const rows = document.querySelectorAll('.activity-row');
            const btn = document.getElementById('show-more-btn');
            let visibleCount = 5;

            btn.style.display = rows.length > visibleCount ? 'inline-block' : 'none';

            btn.addEventListener('click', () => {
                const nextCount = visibleCount + 5;
                rows.forEach((row, index) => {
                    if (index < nextCount) row.style.display = '';
                });
                visibleCount = nextCount;
                if (visibleCount >= rows.length) btn.style.display = 'none';
            });
        });
    </script>
</body>

</html>