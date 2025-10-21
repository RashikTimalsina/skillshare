<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('admin');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SkillShare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <?php include('../includes/layouts/header.php'); ?>
    <?php include('../includes/layouts/sidebar.php'); ?>
    <?php include('../includes/layouts/topbar.php'); ?>

    <main class="main-content">
        <!-- TOP 4 CARDS -->
        <section class="dashboard-overview">
            <?php
            $cards = [
                'Total Users' => "SELECT COUNT(*) as total FROM users",
                'Total Teachers' => "SELECT COUNT(*) as total FROM users WHERE role='teacher'",
                'Total Learners' => "SELECT COUNT(*) as total FROM users WHERE role='learner'",
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
                        <th>User</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $activities = mysqli_query($conn, "SELECT * FROM activities ORDER BY created_at DESC LIMIT 5");
                    if (mysqli_num_rows($activities) > 0) {
                        while ($act = mysqli_fetch_assoc($activities)) {
                            echo "<tr>
                            <td>{$act['created_at']}</td>
                            <td>{$act['username']}</td>
                            <td>{$act['action']}</td>
                          </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center;'>No recent activities</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>

        <!-- QUICK ACTIONS -->
        <section class="quick-actions">
            <div class="action-card" onclick="window.location.href='../pages/admin/users.php'">
                <h3>Manage Users</h3>
                <p>View, edit or remove platform users</p>
            </div>
            <div class="action-card" onclick="window.location.href='../pages/admin/skills.php'">
                <h3>Manage Skills</h3>
                <p>Add, update or delete skills</p>
            </div>
            <div class="action-card" onclick="window.location.href='../pages/admin/feedbacks.php'">
                <h3>Manage Feedback</h3>
                <p>Review feedbacks from learners</p>
            </div>
        </section>
    </main>

    <?php include('../includes/layouts/footer.php'); ?>
</body>

</html>