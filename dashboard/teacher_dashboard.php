<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('teacher');

$teacher_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

            // Get teacher’s enrollments (filtered automatically)
            $teacherEnrollments = getEnrollmentsByRole($conn, $teacher_user_id, $user_role);

            // Get teacher’s feedbacks
            $teacherFeedbacks = getFeedbacksByRole($conn, $teacher_user_id, $user_role);

            // Now you can count or display them:
            $totalEnrollments = count($teacherEnrollments);
            $totalFeedbacks = count($teacherFeedbacks);
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
                <!-- DASHBOARD OVERVIEW -->
                <section class="dashboard-overview">
                    <?php
                    // Fetch all skills linked to this teacher
                    $skill_query = "
                    SELECT s.skill_id
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

                    // Dashboard card queries
                    $cards = [
                        'Total Skills' => "SELECT COUNT(*) AS total FROM teachers WHERE users_user_id = '$teacher_user_id'",
                        'Active Enrollments' => "
                        SELECT COUNT(*) AS total 
                        FROM enrollments e
                        JOIN skills s ON e.skills_skill_id = s.skill_id
                        WHERE e.skills_skill_id IN ($skill_ids_str) AND e.status = 'enrolled'
                    ",
                        'Completed Enrollments' => "
                        SELECT COUNT(*) AS total 
                        FROM enrollments e
                        JOIN skills s ON e.skills_skill_id = s.skill_id
                        WHERE e.skills_skill_id IN ($skill_ids_str) AND e.status = 'completed'
                    ",
                        'Pending Feedbacks' => "
                        SELECT COUNT(*) AS total 
                        FROM feedback f
                        JOIN skills s ON f.skills_skill_id = s.skill_id
                        WHERE f.skills_skill_id IN ($skill_ids_str) AND f.feedback_given = 'no'
                    ",
                    ];

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
                        <tbody id="activities-tbody">
                            <?php
                            $activities_query = "
                        SELECT a.created_at, a.action, a.username AS learner_name
                        FROM activities a
                        JOIN user_skills us ON us.user_id = a.user_id
                        WHERE us.skill_id IN ($skill_ids_str)
                        ORDER BY a.created_at DESC
                    ";
                            $activities_res = mysqli_query($conn, $activities_query);
                            $recent_activities = [];
                            if ($activities_res && mysqli_num_rows($activities_res) > 0) {
                                while ($row = mysqli_fetch_assoc($activities_res)) {
                                    $recent_activities[] = $row;
                                }
                            }

                            foreach ($recent_activities as $i => $act) {
                                echo "<tr class='activity-row' style='" . ($i >= 5 ? "display:none;" : "") . "'>
                                <td>" . date('d M Y, H:i', strtotime($act['created_at'])) . "</td>
                                <td>" . htmlspecialchars($act['action']) . "</td>
                                <td>" . htmlspecialchars($act['learner_name']) . "</td>
                              </tr>";
                            }

                            if (empty($recent_activities)) {
                                echo "<tr><td colspan='3' style='text-align:center;'>No recent activities</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php if (count($recent_activities) > 5): ?>
                        <div style="text-align:center;">
                            <button id="show-more-btn">Show More</button>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- QUICK ACTIONS -->
                <section class="quick-actions">
                    <div class="action-card" onclick="window.location.href='../pages/teacher/skills.php'">
                        <h3>My Skills</h3>
                        <p>View your uploaded skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/teacher/enrollments.php'">
                        <h3>Enrollments</h3>
                        <p>Manage learner enrollments</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/teacher/feedbacks.php'">
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