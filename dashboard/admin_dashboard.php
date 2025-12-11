<?php
include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');
checkRole('admin');

// ====================== DASHBOARD OVERVIEW COUNTS ======================
$cards = [
    'Total Users' => "SELECT COUNT(*) as total FROM users",
    'Total Learners' => "SELECT COUNT(*) as total FROM users WHERE role='learner'",
    'Total Teachers' => "SELECT COUNT(*) as total FROM users WHERE role='teacher'",
    'Total Skills' => "SELECT COUNT(*) as total FROM skills",
];

// Fetch counts
$cardCounts = [];
foreach ($cards as $title => $query) {
    $res = mysqli_query($conn, $query);
    $cardCounts[$title] = mysqli_fetch_assoc($res)['total'] ?? 0;
}

// Fetch recent activities
$activitiesQuery = "SELECT username, action, created_at FROM activities ORDER BY created_at DESC LIMIT 8";
$activitiesRes = mysqli_query($conn, $activitiesQuery);

// Dummy statistics data (can later be dynamic)
$stats = [
    'Active Users' => 128,
    'Completed Courses' => 54,
    'Pending Feedbacks' => 23,
    'New Registrations' => 12
];
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

                <!-- USER GREETING -->
                <h3 style="font-size: 1.5rem; color: #333; font-weight: 200; margin-top: 0rem; margin-bottom: 1rem;">
                    Welcome Back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!
                </h3>

                <!-- DASHBOARD OVERVIEW -->
                <section class="dashboard-overview">
                    <?php
                    $colors = ['#007bff', '#28a745', '#ffc107', '#17a2b8', '#6f42c1', '#fd7e14'];
                    $i = 0;
                    foreach ($cardCounts as $title => $count):
                        $color = $colors[$i % count($colors)];
                    ?>
                        <div class='card' style='border-top: 5px solid <?= $color ?>;'>
                            <h3><?= $title ?></h3>
                            <p><?= $count ?></p>
                        </div>
                    <?php
                        $i++;
                    endforeach;
                    ?>
                </section>


                <!-- MIDDLE SECTION -->
                <div class="dashboard-middle">

                    <!-- Activities Section -->
                    <section class="activities-section">
                        <h3 style="margin-bottom: 0.75rem; color: #333;">Recent Activities</h3>
                        <div class="activity-feed">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($activitiesRes)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['username']) ?></td>
                                            <td><?= htmlspecialchars($row['action']) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Statistics Section -->
                    <section class="statistics-section">
                        <h3 style="margin-bottom: 0.75rem; color: #333;">Statistics</h3>
                        <?php foreach ($stats as $title => $value): ?>
                            <div class="stat-card">
                                <?= $title ?>: <strong><?= $value ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </section>

                </div>

                <!-- QUICK ACTIONS -->
                <h3 style="margin-bottom: 0.0rem; color: #333;">Quick Actions</h3>
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
</body>

</html>