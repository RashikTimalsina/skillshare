<!-- =================CERTIFICATES PAGE(TEACHER)======================= -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';
require_once '../models/Certificate.php';
require_once '../models/User.php';
require_once '../models/Learner.php';
require_once '../models/Skill.php';
require_once '../models/Enrollment.php';

allowRoles(['teacher', 'admin']); // Only teachers/admins
$userId = $_SESSION['user_id'];

// -------------------- Handle POST --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Issue certificate
    if (isset($_POST['issue_certificate'])) {
        $learnerId = (int) ($_POST['learner_id'] ?? 0);
        $skillId = (int) ($_POST['skill_id'] ?? 0);

        if ($learnerId && $skillId) {
            $certificateCode = "CERT-" . strtoupper(bin2hex(random_bytes(4)));
            $issued = Certificate::issue($conn, [
                'learner_id' => $learnerId,
                'course_id' => $skillId,
                'certificate_code' => $certificateCode
            ]);

            $_SESSION[$issued ? 'success' : 'error'] = $issued
                ? "Certificate issued successfully."
                : "Certificate already exists or unauthorized.";
        } else {
            $_SESSION['error'] = "Please select a learner and a skill.";
        }
    }

    // Revoke certificate
    if (isset($_POST['revoke_certificate'])) {
        $certificateId = (int) ($_POST['certificate_id'] ?? 0);
        if ($certificateId) {
            $revoked = Certificate::revoke($conn, $certificateId);
            $_SESSION[$revoked ? 'success' : 'error'] = $revoked
                ? "Certificate revoked successfully."
                : "Cannot revoke unauthorized certificate.";
        }
    }

    header("Location: certificates.php");
    exit();
}

// -------------------- Fetch Data --------------------
// Existing certificates
$certificates = Certificate::getAll($conn);

// Pending enrollments (completed but no certificate)
$stmt = $conn->prepare("
    SELECT DISTINCT e.enrollment_id, u.user_id AS learner_id, u.user_name, s.skill_id, s.skill_name
    FROM enrollments e
    JOIN users u ON e.users_user_id = u.user_id
    JOIN skills s ON e.skills_skill_id = s.skill_id
    LEFT JOIN certificates c ON c.learner_id = u.user_id AND c.course_id = s.skill_id
    WHERE e.status='completed' AND c.id IS NULL
    ORDER BY e.enrolled_at DESC
");
$stmt->execute();
$completedEnrollments = $stmt->get_result();

// Build unique dropdown sets
$learnersSet = [];
$skillsSet = [];
while ($row = $completedEnrollments->fetch_assoc()) {
    $learnersSet[$row['learner_id']] = $row['user_name'];
    $skillsSet[$row['skill_id']] = $row['skill_name'];
}

$learnersOptions = '';
foreach ($learnersSet as $id => $name) {
    $learnersOptions .= "<option value='{$id}'>" . htmlspecialchars($name) . "</option>";
}

$skillsOptions = '';
foreach ($skillsSet as $id => $name) {
    $skillsOptions .= "<option value='{$id}'>" . htmlspecialchars($name) . "</option>";
}

$stmt->close();
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/certificates.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>
    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>
        <div class="main-content">

            <h2>Certificates</h2>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
                                                    unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']);
                                                unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Issue Form -->
            <?php if ($learnersOptions && $skillsOptions): ?>
                <form method="POST" class="issue-certificate-form">
                    <h3>Issue New Certificate</h3>
                    <select name="learner_id" required>
                        <option value="">Select Learner</option>
                        <?= $learnersOptions ?>
                    </select>
                    <select name="skill_id" required>
                        <option value="">Select Skill</option>
                        <?= $skillsOptions ?>
                    </select>
                    <button type="submit" name="issue_certificate">Issue Certificate</button>
                </form>
            <?php else: ?>
                <p>No completed enrollments pending certificate issuance.</p>
            <?php endif; ?>

            <!-- Certificates Table -->
            <div class="table-container">
                <table class="certificates-table">
                    <thead>
                        <tr>
                            <?php if ($role === 'admin'): ?><th>ID</th><?php endif; ?>
                            <th>Learner</th>
                            <th>Skill</th>
                            <th>Certificate Code</th>
                            <th>Issued Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($certificates)): ?>
                            <?php foreach ($certificates as $c): ?>
                                <tr>
                                    <?php if ($role === 'admin'): ?>
                                        <td><?= htmlspecialchars($c['id']) ?></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($c['learner_name']) ?></td>
                                    <td><?= htmlspecialchars($c['skill_name']) ?></td>
                                    <td><?= htmlspecialchars($c['certificate_code']) ?></td>
                                    <td><?= htmlspecialchars($c['issued_date']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $c['status'] === 'active' ? 'active' : 'revoked' ?>">
                                            <?= ucfirst(htmlspecialchars($c['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($c['status'] === 'active'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="certificate_id" value="<?= htmlspecialchars($c['id']) ?>">
                                                <button type="submit" name="revoke_certificate" class="btn-revoke">Revoke</button>
                                            </form>
                                        <?php else: ?>
                                            <span>Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">No certificates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const revokeForms = document.querySelectorAll('form button[name="revoke_certificate"]');
        revokeForms.forEach(button => {
            button.addEventListener('click', function(e) {
                const confirmed = confirm('Are you sure you want to revoke this certificate?');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    });
</script>

<?php include('../includes/layouts/footer.php'); ?>