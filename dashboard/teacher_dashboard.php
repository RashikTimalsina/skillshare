<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('teacher');

$teacher_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Ensure user_name is available
$user_name = $_SESSION['user_name'] ?? null;

if (!$user_name) {
    $res = mysqli_query($conn, "SELECT user_name FROM users WHERE user_id = '$teacher_user_id'");
    $row = mysqli_fetch_assoc($res);
    $user_name = $row['user_name'] ?? 'Unknown User';
}

// Log dashboard visit only once per session
if (!isset($_SESSION['teacher_dashboard_visited'])) {
    logActivity($conn, $teacher_user_id, $user_name, "Visited Teacher Dashboard");
    $_SESSION['teacher_dashboard_visited'] = true;
}

// Fetch skills taught by this teacher
$skill_query = "
    SELECT skill_id 
    FROM skills s
    JOIN teachers t ON t.skills_skill_id = s.skill_id
    WHERE t.users_user_id = '$teacher_user_id'
";
$skills_res = mysqli_query($conn, $skill_query);
$skill_ids = [];
while ($row = mysqli_fetch_assoc($skills_res)) {
    $skill_ids[] = $row['skill_id'];
}
$skill_ids_str = $skill_ids ? implode(',', $skill_ids) : '0';

// Dashboard cards
$cards = [
    'Total Skills' => "SELECT COUNT(*) AS total FROM teachers WHERE users_user_id = '$teacher_user_id'",
    'Active Enrollments' => "SELECT COUNT(*) AS total FROM enrollments WHERE skills_skill_id IN ($skill_ids_str) AND status='enrolled'",
    'Completed Enrollments' => "SELECT COUNT(*) AS total FROM enrollments WHERE skills_skill_id IN ($skill_ids_str) AND status='completed'",
    'Pending Feedbacks' => "SELECT COUNT(*) AS total FROM feedback WHERE skills_skill_id IN ($skill_ids_str) AND feedback_given='no'",
];

// Recent activities (last 10)
$activities_query = "
    SELECT a.created_at, a.action, a.username AS learner_name
    FROM activities a
    JOIN user_skills us ON us.user_id = a.user_id
    WHERE us.skill_id IN ($skill_ids_str)
    ORDER BY a.created_at DESC
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
    <title>Teacher Dashboard | SkillShare</title>
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
                                <th>Learner</th>
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
                                        <td>" . htmlspecialchars($act['learner_name']) . "</td>
                                      </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center;'>No recent activities</td></tr>";
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
                    <div class="action-card" onclick="window.location.href='../pages/skills.php'">
                        <h3>My Skills</h3>
                        <p>View your uploaded skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/enrollments.php'">
                        <h3>Enrollments</h3>
                        <p>Manage learner enrollments</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/feedbacks.php'">
                        <h3>Feedbacks</h3>
                        <p>View feedbacks from learners</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/certificates.php'">
                        <h3>Certificates</h3>
                        <p>Manage and issue course certificates</p>
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

            if (btn) {
                btn.addEventListener('click', () => {
                    const nextCount = visibleCount + 5;
                    rows.forEach((row, index) => {
                        if (index < nextCount) row.style.display = '';
                    });
                    visibleCount = nextCount;
                    if (visibleCount >= rows.length) btn.style.display = 'none';
                });
            }
        });
    </script>
</body>

</html>