<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('learner');

$learner_id = $_SESSION['user_id'];

// Fetch last 10 activities
$limit = 10;
$result = mysqli_query($conn, "
    SELECT created_at, action
    FROM activities
    WHERE user_id = '$learner_id'
    ORDER BY created_at DESC
    LIMIT $limit
");

$recent_activities = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
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
                <!-- DASHBOARD OVERVIEW -->
                <section class="dashboard-overview">
                    <?php
                    $cards = [
                        'Skills Enrolled' => "
                        SELECT COUNT(DISTINCT e.skills_skill_id) AS total
                        FROM enrollments e
                        WHERE e.users_user_id = '$learner_id'
                    ",
                        'Skills Completed' => "
                        SELECT COUNT(DISTINCT e.skills_skill_id) AS total
                        FROM enrollments e
                        WHERE e.users_user_id = '$learner_id'
                        AND e.status = 'inactive'
                    ",
                        'Certificates Earned' => "
                        SELECT COUNT(*) AS total
                        FROM certificates c
                        WHERE c.learner_id = '$learner_id'
                        AND c.status = 'active'
                    ",
                        'Feedback Given' => "
                        SELECT COUNT(*) AS total
                        FROM feedback f
                        WHERE f.users_user_id = '$learner_id'
                        AND f.feedback_given = 'yes'
                    "
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
                            </tr>
                        </thead>
                        <tbody id="activities-tbody">
                            <?php foreach ($recent_activities as $i => $act): ?>
                                <tr class="activity-row" style="<?= $i >= 5 ? 'display:none;' : '' ?>">
                                    <td><?= date('d M Y, H:i', strtotime($act['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($act['action']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_activities)) echo "<tr><td colspan='2'>No recent activities</td></tr>"; ?>
                        </tbody>
                    </table>
                    <div style="text-align:center;">
                        <button id="show-more-btn">Show More</button>
                    </div>
                </section>

                <!-- QUICK ACTIONS -->
                <section class="quick-actions">
                    <div class="action-card" onclick="window.location.href='../pages/learner/enroll_skill.php'">
                        <h3>Enroll in New Skill</h3>
                        <p>Browse and join available skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/learner/my_skills.php'">
                        <h3>My Skills</h3>
                        <p>View your enrolled and completed skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/learner/give_feedback.php'">
                        <h3>Give Feedback</h3>
                        <p>Share feedback on your learning experiences</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/learner/certificates.php'">
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