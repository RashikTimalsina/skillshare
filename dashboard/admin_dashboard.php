<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('admin');

// Fetch last 10 activities
$limit = 10;
$user_id = $_SESSION['user_id'];
$result = mysqli_query($conn, "
    SELECT created_at, action
    FROM activities
    WHERE user_id = '$user_id'
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
    <title>Admin Dashboard | SkillShare</title>
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
                        'Total Users' => "SELECT COUNT(*) as total FROM users",
                        'Total Learners' => "SELECT COUNT(*) as total FROM users WHERE role='learner'",
                        'Total Teachers' => "SELECT COUNT(*) as total FROM users WHERE role='teacher'",
                        'Total Skills' => "SELECT COUNT(*) as total FROM skills",
                    ];
                    foreach ($cards as $title => $query) {
                        $res = mysqli_query($conn, $query);
                        $count = mysqli_fetch_assoc($res)['total'];
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
                    <button id="show-more-btn">Show More</button>
                </section>

                <!-- QUICK ACTIONS -->
                <section class="quick-actions">
                    <div class="action-card" onclick="window.location.href='../pages/users.php'">
                        <h3>Manage Users</h3>
                        <p>View, edit or remove platform users</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/skills.php'">
                        <h3>Manage Skills</h3>
                        <p>Add, update or delete skills</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/categories.php'">
                        <h3>Manage Categories</h3>
                        <p>Add, update or delete skill categories</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='../pages/feedbacks.php'">
                        <h3>Manage Feedback</h3>
                        <p>Review feedback from learners</p>
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