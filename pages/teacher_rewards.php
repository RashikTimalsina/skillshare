<!-- =================TEACHER REWARDS PAGE (TEACHER)======================= -->

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../middlewares/auth_check.php';
require_once __DIR__ . '/../middlewares/role_check.php';
require_once __DIR__ . '/../middlewares/teacher_active_check.php';
require_once __DIR__ . '/../models/TeacherReward.php';
require_once __DIR__ . '/../includes/flash_messages.php';

allowRoles(['teacher']);

$userId = $_SESSION['user_id'];

// Get teacher ID
$stmt = $conn->prepare("SELECT id FROM teachers WHERE users_user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Fetch all active rewards (no expiry)
$rewards = TeacherReward::getActiveByTeacher($conn, $teacher['id']);

require_once __DIR__ . '/../includes/layouts/header.php';
?>

<link rel="stylesheet" href="../assets/css/pages/teacher_rewards.css">
<link rel="stylesheet" href="../assets/css/layout.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>
        <div class="main-content">
            <div class="page-header">
                <h2>My Rewards</h2>
                <p>Recognition and rewards awarded by the platform</p>
            </div>

            <?php if (empty($rewards)): ?>
                <div class="empty-state">
                    <p>No rewards have been awarded yet.</p>
                </div>
            <?php else: ?>
                <div class="rewards-grid">
                    <?php foreach ($rewards as $r): ?>
                        <div class="reward-card">
                            <div class="points"><?= (int)$r['points'] ?> pts</div>
                            <div class="reward-body">
                                <p><?= htmlspecialchars($r['reason']) ?></p>
                                <div class="meta">
                                    <span>Awarded on: <?= date('d M Y', strtotime($r['awarded_date'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/layouts/footer.php'; ?><div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>
        <div class="main-content">
            <div class="page-header">
                <h2>My Rewards</h2>
                <p>Recognition and rewards awarded by the platform</p>
            </div>

            <?php if (empty($rewards)): ?>
                <div class="empty-state">
                    <p>No rewards have been awarded yet.</p>
                </div>
            <?php else: ?>
                <div class="rewards-grid">
                    <?php foreach ($rewards as $r): ?>
                        <div class="reward-card">
                            <div class="points"><?= (int)$r['points'] ?> pts</div>
                            <div class="reward-body">
                                <p><?= htmlspecialchars($r['reason']) ?></p>
                                <div class="meta">
                                    <span>Awarded on: <?= date('d M Y', strtotime($r['awarded_date'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/layouts/footer.php'; ?>