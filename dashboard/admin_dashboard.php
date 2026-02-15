<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load database connection (creates $conn)
include('../includes/config.php');

// Load common helper functions used across the system
require_once __DIR__ . '/../includes/functions.php';

// Ensure the user is logged in; redirects if session is missing
include('../middlewares/session_check.php');

// Ensure only authorized roles (admin) can access this dashboard
include('../middlewares/role_check.php');

allowRoles(['admin']);

$adminStats = getAdminStats($conn);


// ====================== DASHBOARD OVERVIEW COUNTS ======================
// Defines dashboard cards with their corresponding SQL queries
// Each query returns a single aggregate value
$cards = [
    'Total Users' => "SELECT COUNT(*) as total FROM users",
    'Total Learners' => "SELECT COUNT(*) as total FROM users WHERE role='learner'",
    'Total Teachers' => "SELECT COUNT(*) as total FROM users WHERE role='teacher'",
    'Total Skills' => "SELECT COUNT(*) as total FROM skills",
];

// Execute each query and store the resulting count
$cardCounts = [];
foreach ($cards as $title => $query) {
    $res = mysqli_query($conn, $query);              // Execute SQL query
    $cardCounts[$title] = mysqli_fetch_assoc($res)['total'] ?? 0; // Safely extract count
}

// Fetch last 5 activities for admin (all users)
$recent_activities = getRecentActivities($conn, 'admin', $_SESSION['user_id'], 5);

?>


<!-- Admin dashboard UI starts here -->
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Standard meta configuration -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SkillShare</title>

    <!-- Global layout and dashboard-specific styles -->
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pM2L6nMxU...yourhash..." crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>
    <div class="layout-wrapper">
        <!-- Sidebar navigation -->
        <?php include('../includes/layouts/sidebar.php'); ?>

        <div class="main-content-wrapper">
            <!-- Top navigation bar -->
            <?php include('../includes/layouts/topbar.php'); ?>

            <main class="main-content">

                <!-- Logged-in admin greeting -->
                <h3>
                    Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!
                </h3>

                <!-- DASHBOARD METRIC CARDS -->
                <section class="dashboard-overview">
                    <?php
                    $colors = ['#007bff', '#28a745', '#ffc107', '#17a2b8'];
                    $icons = ['fa-users', 'fa-user-graduate', 'fa-chalkboard-teacher', 'fa-lightbulb']; // assign icons
                    $i = 0;
                    foreach ($cardCounts as $title => $count):
                        $color = $colors[$i % count($colors)];
                        $icon = $icons[$i % count($icons)];
                    ?>
                        <div class='card' style='border-top: 5px solid <?= $color ?>;'>
                            <h3><i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i> <?= $title ?></h3>
                            <p><?= $count ?></p>
                        </div>
                    <?php $i++;
                    endforeach; ?>
                </section>

                <section class="dashboard-overview">
                    <?php
                    $colors = [' #6f42c1', '#fd7e14', '#20c997', '#e83e8c'];
                    $icons = ['fa-tasks', 'fa-star', 'fa-certificate', 'fa-chart-line'];
                    $i = 0;
                    foreach ($adminStats as $title => $count):
                        $color = $colors[$i % count($colors)];
                        $icon = $icons[$i % count($icons)];
                    ?>
                        <div class='card' style='border-top: 5px solid <?= $color ?>;'>
                            <h3><i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i> <?= str_replace('_', ' ', ucwords($title)) ?></h3>
                            <p><?= $count ?></p>
                        </div>
                    <?php $i++;
                    endforeach; ?>
                </section>



                <!-- MIDDLE SECTION -->
                <div class="dashboard-middle">

                    <!-- Recent Activity Log -->
                    <section class="activities-section">
                        <h3>Recent Activities</h3>

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
                                    <!-- Loop through activity records -->
                                    <?php foreach ($recent_activities as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['username']) ?></td>
                                            <td><?= htmlspecialchars($row['action']) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </section>
<!-- 
                    <section class="statistics-section" id="admin-bar-chart">
                        <h3>Platform Statistics</h3>
                        <div class="bar-chart-container">
                            <?php foreach ($adminStats as $title => $count): ?>
                                <div class="bar-wrapper">
                                    <div class="bar" data-value="<?= $count ?>"></div>
                                    <span class="bar-label"><?= str_replace('_', ' ', ucwords($title)) ?></span>
                                    <span class="bar-value"><?= $count ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section> -->


                </div>

                <!-- ADMIN QUICK ACTION SHORTCUTS -->
                <h3>Quick Actions</h3>
                <section class="quick-actions">
                    <?php
                    $quickActioncolors = ['#6f42c1', '#28a745', '#ffc107', '#17a2b8'];
                    $actions = [
                        ['title' => 'Manage Users', 'icon' => 'fa-users-cog', 'link' => '../pages/users.php', 'desc' => 'View, edit or remove platform users'],
                        ['title' => 'Manage Skills', 'icon' => 'fa-lightbulb', 'link' => '../pages/skills.php', 'desc' => 'Add, update or delete skills'],
                        ['title' => 'Approve Teacher Status', 'icon' => 'fa-user-check', 'link' => '../pages/pending_teachers.php', 'desc' => 'Activate the teacher status'],
                        ['title' => 'Give Rewards', 'icon' => 'fa-award', 'link' => '../pages/teacher_rewards_admin.php', 'desc' => 'Review feedback from learners'],
                    ];
                    ?>

                    <?php foreach ($actions as $i => $action):
                        $color = $quickActioncolors[$i % count($quickActioncolors)];
                    ?>
                        <div class="action-card" onclick="window.location.href='<?= $action['link'] ?>'">
                            <h3><i class="fas <?= $action['icon'] ?>" style="color: <?= $color ?>;"></i> <?= $action['title'] ?></h3>
                            <p><?= $action['desc'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>


            </main>

            <!-- Footer -->
            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>

    <!-- Layout-related JS -->
    <script src="../assets/js/layout.js" defer></script>

    <!-- <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bars = document.querySelectorAll('#admin-bar-chart .bar');
            if (!bars.length) return;

            const maxValue = Math.max(...Array.from(bars).map(bar => parseInt(bar.dataset.value)));

            bars.forEach(bar => {
                const value = parseInt(bar.dataset.value);
                const heightPercent = maxValue ? (value / maxValue) * 100 : 0;
                bar.style.height = heightPercent > 0 ? heightPercent + '%' : '5px';
            });

            const activitiesSection = document.querySelector('.activities-section');
            const statsSection = document.querySelector('#admin-bar-chart');
            if (activitiesSection && statsSection) {
                statsSection.style.minHeight = activitiesSection.offsetHeight + 'px';
            }
        });
    </script> -->

</body>

</html>