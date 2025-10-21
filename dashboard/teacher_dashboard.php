<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('teacher');
$teacher_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | SkillShare</title>
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
                'Total Skills' => "SELECT COUNT(*) as total FROM skills WHERE teacher_id='$teacher_id'",
                'Active Enrollments' => "SELECT COUNT(*) as total FROM enrollments e JOIN skills s ON e.skill_id=s.id WHERE s.teacher_id='$teacher_id' AND e.status!='completed'",
                'Completed Enrollments' => "SELECT COUNT(*) as total FROM enrollments e JOIN skills s ON e.skill_id=s.id WHERE s.teacher_id='$teacher_id' AND e.status='completed'",
                'Pending Feedbacks' => "SELECT COUNT(*) as total FROM enrollments e JOIN skills s ON e.skill_id=s.id WHERE s.teacher_id='$teacher_id' AND e.feedback_given='no'",
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
                        <th>Learner</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $activities = mysqli_query($conn, "
                SELECT a.created_at, a.action, u.username AS learner_name
                FROM activities a
                LEFT JOIN users u ON a.learner_id = u.user_id
                JOIN skills s ON a.skill_id=s.id
                WHERE s.teacher_id='$teacher_id'
                ORDER BY a.created_at DESC
                LIMIT 5
            ");
                    if (mysqli_num_rows($activities) > 0) {
                        while ($row = mysqli_fetch_assoc($activities)) {
                            echo "<tr>
                            <td>{$row['created_at']}</td>
                            <td>{$row['action']}</td>
                            <td>{$row['learner_name']}</td>
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
        </section>
    </main>

    <?php include('../includes/layouts/footer.php'); ?>
</body>

</html>