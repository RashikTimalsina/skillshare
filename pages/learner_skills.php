<!-- =================MY SKILLS======================= -->
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/functions.php';

require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';

// Allow learner only
allowRoles(['learner']);

require_once '../models/Enrollment.php';
require_once '../models/Certificate.php';

$learnerId = $_SESSION['user_id'];
$learnerName = $_SESSION['user_name'] ?? 'Learner';


// Handle claiming certificate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_certificate'])) {
    $enrollmentId = $_POST['enrollment_id'] ?? null;
    if ($enrollmentId) {
        // Auto-issue certificate using the updated logic
        $certId = Certificate::createForEnrollment($conn, $enrollmentId, $learnerId);
        if ($certId) {
            $_SESSION['success'] = "Certificate claimed successfully!";
        } else {
            $_SESSION['error'] = "Unable to claim certificate. It may already exist or no issuer is available.";
        }
        header("Location: learner_skills.php");
        exit();
    }
}

// Fetch learner-specific enrollments
$enrollments = Enrollment::getByLearner($conn, $learnerId);

// Fetch all certificates for this learner for faster lookup
$certificates = Certificate::getAllByLearner($conn, $learnerId);
$certMap = [];
foreach ($certificates as $cert) {
    $certMap[$cert['course_id']] = $cert; // map skill_id to certificate array
}



include '../includes/layouts/header.php';
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/users.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>

    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>

        <main class="main-content">
            <h2>My Skills</h2>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
                                                    unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']);
                                                unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Skill Name</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Enrolled On</th>
                            <th>Certificate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollments)): ?>
                            <?php foreach ($enrollments as $index => $row): ?>
                                <?php
                                $skillId = $row['skills_skill_id'];
                                $status = $row['status'];

                                // Progress bar
                                $progress = match ($status) {
                                    'enrolled' => 25,
                                    'completed' => 100,
                                    'dropped' => 0,
                                    default => 0
                                };

                                $certificate = $certMap[$skillId] ?? null;
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($row['skill_name']) ?></td>
                                    <td><?= htmlspecialchars($row['category_name'] ?? '—') ?></td>
                                    <td><?= ucfirst($status) ?></td>
                                    <td>
                                        <div class="skill-progress-bar">
                                            <div class="skill-progress-bar-inner" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                        <small><?= $progress ?>%</small>
                                    </td>
                                    <td><?= date('d M Y', strtotime($row['enrolled_at'])) ?></td>
                                    <td>
                                        <?php if ($certificate && $certificate['status'] === 'active'): ?>
                                            <a href="../pages/view_certificate.php?id=<?= $certificate['id'] ?>" target="_blank">View here</a>
                                        <?php elseif ($status === 'completed' && !$certificate): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                                <button type="submit" name="claim_certificate">Claim Certificate</button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">You have not enrolled in any skills yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <?php include '../includes/layouts/footer.php'; ?>
    </div>
</div>

<style>
    /* Simple progress bar styling */
    .skill-progress-bar {
        width: 100%;
        background: #e0e0e0;
        height: 12px;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4px;
    }

    .skill-progress-bar-inner {
        height: 100%;
        background: #4e73df;
        border-radius: 6px 0 0 6px;
    }

    .users-table button {
        padding: 3px 8px;
        font-size: 0.85rem;
        border: none;
        background-color: #1cc88a;
        color: white;
        border-radius: 4px;
        cursor: pointer;
    }

    .users-table button:hover {
        background-color: #17a673;
    }
</style>