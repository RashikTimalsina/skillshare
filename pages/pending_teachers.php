<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';

allowRoles(['admin']); // Only admin can access

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($teacherId && in_array($action, ['approve', 'reject'])) {

        $adminId = $_SESSION['user_id'];

        // Fetch teacher name (for readable activity)
        $stmt = $conn->prepare("SELECT user_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $stmt->bind_result($teacherName);
        $stmt->fetch();
        $stmt->close();

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE teachers SET status='active' WHERE users_user_id=?");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $stmt->close();

            logActivity(
                $conn,
                $adminId,
                "Approved teacher account: {$teacherName}"
            );

            $_SESSION['success'] = "Teacher approved successfully!";
        }

        if ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE teachers SET status='none' WHERE users_user_id=?");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $stmt->close();

            logActivity(
                $conn,
                $adminId,
                "Rejected teacher account: {$teacherName}"
            );

            $_SESSION['success'] = "Teacher rejected!";
        }

        header("Location: pending_teachers.php");
        exit;
    }
}

// Fetch all pending teachers
$query = "
    SELECT t.users_user_id, t.status, u.user_name, u.email, u.profile_pic
    FROM teachers t
    JOIN users u ON t.users_user_id = u.user_id
    WHERE t.status IN ('none','inactive')
    ORDER BY u.user_name ASC
";
$res = $conn->query($query);

$pendingTeachers = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $pendingTeachers[] = $row;
    }
}

include '../includes/layouts/header.php';
?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/users.css">

<div class="layout-wrapper">
    <?php include '../includes/layouts/sidebar.php'; ?>
    <div class="main-content-wrapper">
        <?php include '../includes/layouts/topbar.php'; ?>

        <div class="main-content">
            <h2>Pending Teacher Approvals</h2>
            <?php include '../includes/flash_messages.php'; ?>

            <?php if (!empty($pendingTeachers)): ?>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Profile</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingTeachers as $index => $teacher): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($teacher['user_name']) ?></td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td>
                                        <?php if ($teacher['profile_pic']): ?>
                                            <img src="../assets/images/<?= htmlspecialchars($teacher['profile_pic']) ?>" alt="Profile" width="40">
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?= ucfirst($teacher['status']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="teacher_id" value="<?= $teacher['users_user_id'] ?>">
                                            <button type="submit" name="action" value="approve" class="action-link">✅ Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="teacher_id" value="<?= $teacher['users_user_id'] ?>">
                                            <button type="submit" name="action" value="reject" class="action-link delete">❌ Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending teachers at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/layouts/footer.php'; ?>