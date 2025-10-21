<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('learner'); // restrict access to learners only

$learner_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard | SkillShare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <?php include('../includes/layouts/header.php'); ?>
    <?php include('../includes/layouts/sidebar.php'); ?>
    <?php include('../includes/layouts/topbar.php'); ?>

    <main class="main-content">

        <!-- ===== TOP 4 CARDS ===== -->
        <section class="dashboard-overview">
            <div class="card">
                <h3>Enrolled Skills</h3>
                <p>
                    <?php
                    $enrollments = mysqli_query($conn, "SELECT COUNT(*) AS total FROM enrollments WHERE users_user_id='$learner_id'");
                    echo mysqli_fetch_assoc($enrollments)['total'];
                    ?>
                </p>
            </div>

            <div class="card">
                <h3>Completed Skills</h3>
                <p>
                    <?php
                    $completed = mysqli_query($conn, "SELECT COUNT(*) AS total FROM enrollments WHERE users_user_id='$learner_id' AND status='completed'");
                    echo mysqli_fetch_assoc($completed)['total'];
                    ?>
                </p>
            </div>

            <div class="card">
                <h3>Certificates Earned</h3>
                <p>
                    <?php
                    $certificates = mysqli_query($conn, "SELECT COUNT(*) AS total FROM certificates WHERE learner_id='$learner_id' AND status='active'");
                    echo mysqli_fetch_assoc($certificates)['total'];
                    ?>
                </p>
            </div>

            <div class="card">
                <h3>Pending Feedbacks</h3>
                <p>
                    <?php
                    $feedbacks = mysqli_query($conn, "SELECT COUNT(*) AS total FROM feedback WHERE users_user_id='$learner_id' AND feedback_given='no'");
                    echo mysqli_fetch_assoc($feedbacks)['total'];
                    ?>
                </p>
            </div>
        </section>

        <!-- ===== MIDDLE SECTION: TWO COLUMNS ===== -->
        <section class="dashboard-middle">

            <!-- LEFT COLUMN: RECENT ACTIVITIES -->
            <div class="dashboard-column recent-activities">
                <h2>Recent Activities</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $activities = mysqli_query($conn, "
                            SELECT created_at, action, username AS teacher_name
                            FROM activities
                            WHERE learner_id='$learner_id'
                            ORDER BY created_at DESC
                            LIMIT 5
                        ");

                        if (mysqli_num_rows($activities) > 0) {
                            while ($row = mysqli_fetch_assoc($activities)) {
                                $date = date("d M, Y H:i", strtotime($row['created_at']));
                                echo "<tr>
                                <td>{$date}</td>
                                <td>{$row['action']}</td>
                                <td>{$row['teacher_name']}</td>
                              </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center;'>No recent activities</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- RIGHT COLUMN: UPCOMING COURSES
            <div class="dashboard-column upcoming-courses">
                <h2>Upcoming Courses</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Teacher</th>
                            <th>Start Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $upcoming = mysqli_query($conn, "
                            SELECT s.skill_name, u.full_name AS teacher_name, e.enrolled_at, e.status FROM enrollments e
                            JOIN skills s ON e.skills_skill_id = s.skill_id
                            JOIN teachers t ON t.skills_skill_id = s.skill_id
                            JOIN users u ON u.user_id =t.users.user_id
                            WHERE e.users_user_id = '$learner_id'
                            AND e.status = 'enrolled'
                            ORDER BY e.enrolled_at ASC
                            LIMIT 5
                        ");

                        if (mysqli_num_rows($upcoming) > 0) {
                            while ($course = mysqli_fetch_assoc($upcoming)) {
                                $start_date = date("d M, Y", strtotime($course['enrolled_at']));
                                $status_color = $course['status'] == 'completed' ? 'green' : ($course['status'] == 'enrolled' ? 'orange' : 'gray');
                                echo "<tr>
                                <td>{$course['skill_name']}</td>
                                <td>{$course['teacher_name']}</td>
                                <td>{$start_date}</td>
                                <td style='color:{$status_color}'>" . ucfirst($course['status']) . "</td>
                              </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>No upcoming courses</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div> -->

        </section>

        <!-- ===== QUICK ACTIONS ===== -->
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
        </section>

    </main>

    <?php include('../includes/layouts/footer.php'); ?>
</body>

</html>