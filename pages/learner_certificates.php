<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';
require_once '../models/Certificate.php';

// Only learner can view this
allowRoles(['learner']);

$learnerId = $_SESSION['user_id'] ?? null;

// Fetch all certificates for this learner
$certificates = Certificate::getAllByLearner($conn, $learnerId);

include '../includes/layouts/header.php';
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/users.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>

        <main class="main-content">
            <h2>My Certificates</h2>

            <?php if (!empty($certificates)): ?>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Skill</th>
                                <th>Issued On</th>
                                <th>Certificate Code</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $index => $cert): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($cert['skill_name']) ?></td>
                                    <td><?= date('d M Y', strtotime($cert['issued_date'])) ?></td>
                                    <td><?= htmlspecialchars($cert['certificate_code']) ?></td>
                                    <td>
                                        <a href="view_certificate.php?id=<?= $cert['id'] ?>" target="_blank" style="color:#6c00bb;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>You have not received any certificates yet.</p>
            <?php endif; ?>

        </main>

        <?php include '../includes/layouts/footer.php'; ?>
    </div>
</div>