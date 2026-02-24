<?php
// topbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

// Fetch counts
$user_id = $_SESSION['user_id'] ?? null;
$unread_notifications = 0;
$unread_messages = 0;

if ($user_id) {
    // Messages
    $msg_res = mysqli_query($conn, "SELECT COUNT(*) AS total_unread FROM messages WHERE receiver_id='$user_id' AND is_read='no'");
    $unread_messages = ($msg_res && mysqli_num_rows($msg_res)) ? mysqli_fetch_assoc($msg_res)['total_unread'] : 0;
}

// Determine dashboard title
$role = $_SESSION['user_role'] ?? 'learner';
$dashboard_title = match ($role) {
    'admin' => 'Admin Dashboard',
    'teacher' => 'Teacher Dashboard',
    default => 'Learner Dashboard',
};

// Topbar icons configuration
$topbar_icons = [
    [
        'href' => '../pages/messages.php',
        'icon' => 'chat_bubble',
        'badge' => $unread_messages,
        'title' => 'Messages'
    ],
    [
        'href' => '../pages/profile.php',
        'icon' => 'account_circle',
        'badge' => 0,
        'title' => 'Profile'
    ],
    [
        'href' => '../auth/logout.php',
        'icon' => 'logout',
        'badge' => 0,
        'title' => 'Logout',
        'confirm' => true
    ]
];
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dashboard_title) ?></title>

    <!-- Link your main layout CSS -->
    <link rel="stylesheet" href="../../assets/css/layout.css">

    <!-- Google Material Symbols -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!--  FontAwesome -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
         -->
</head>

<body>
    <header class="topbar">
        <div class="topbar-left">

            <a href="<?php
                        if ($_SESSION['user_role'] === 'admin') echo '../dashboard/admin_dashboard.php';
                        elseif ($_SESSION['user_role'] === 'teacher') echo '../dashboard/teacher_dashboard.php';
                        else echo '../dashboard/learner_dashboard.php';
                        ?>" class="topbar-logo">
                <img src="../assets/images/logo.png" alt="SkillShare" style="height:50px;  width: 100%;
  object-fit: contain;">
            </a>

            <a href=" <?php
                        if ($_SESSION['user_role'] === 'admin') echo '../dashboard/admin_dashboard.php';
                        elseif ($_SESSION['user_role'] === 'teacher') echo '../dashboard/teacher_dashboard.php';
                        else echo '../dashboard/learner_dashboard.php';
                        ?>" class="topbar-title">
                <?= htmlspecialchars($dashboard_title) ?>
            </a>

        </div>

        <div class="topbar-right">
            <?php foreach ($topbar_icons as $item): ?>
                <a href="<?= $item['href'] ?>" class="topbar-icon" title="<?= $item['title'] ?>"
                    <?= isset($item['confirm']) && $item['confirm'] ? "onclick=\"return confirm('Are you sure you want to logout?');\"" : "" ?>>
                    <?php if (!empty($item['icon'])): ?>
                        <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
                    <?php else: ?>
                        <?= htmlspecialchars($item['title']) ?>
                    <?php endif; ?>

                    <?php if (!empty($item['badge'])): ?>
                        <span class="badge"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </header>
</body>

</html>