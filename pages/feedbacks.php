<!-- ======== FEEDBACK PAGE ======== -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('../includes/config.php');
include('../includes/functions.php');
include('../middlewares/session_check.php');
include('../middlewares/role_check.php');

allowRoles(['admin', 'learner', 'teacher']);

require_once '../models/Feedback.php';
require_once '../models/User.php';
require_once '../models/Skill.php';

$role = $_SESSION['user_role'] ?? '';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserName = $_SESSION['user_name'] ?? 'Unknown';

// ===================== POST HANDLERS =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    // ---------- ADD FEEDBACK ----------
    if (isset($_POST['add_feedback']) && $role === 'learner') {
        $skill_id = (int)($_POST['skills_skill_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $feedback_given = ($_POST['feedback_given'] ?? 'no') === 'yes' ? 'yes' : 'no';

        if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1-5.";

        if (!isSkillCompleted($conn, $currentUserId, $skill_id)) {
            $errors[] = "You can only leave feedback for completed skills.";
        }

        if (Feedback::existsForUserAndSkill($conn, $currentUserId, $skill_id)) {
            $errors[] = "Feedback already exists for this skill.";
        }

        if ($errors) {
            $_SESSION['feedback_errors'] = $errors;
            $_SESSION['feedback_form_data'] = $_POST;
            header("Location: feedbacks.php");
            exit();
        }

        $id = Feedback::create($conn, [
            'users_user_id' => $currentUserId,
            'skills_skill_id' => $skill_id,
            'rating' => $rating,
            'comment' => $comment,
            'feedback_given' => $feedback_given
        ]);

        if ($id && $skill_id) {
            $skill = Skill::getById($conn, $skill_id);
            $teacherName = 'Unknown Teacher';

            // Fetch teacher offering this skill
            $stmt = $conn->prepare("
                 SELECT u.user_name AS teacher_name
                 FROM user_skills us
                 JOIN users u ON us.user_id = u.user_id
                 WHERE us.skill_id = ?
                AND us.role = 'teacher'
                LIMIT 1
            ");
            $stmt->bind_param("i", $skill_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $teacherName = $row['teacher_name'] ?? 'Unknown Teacher';
            }
            $stmt->close();

            logActivity(
                $conn,
                $currentUserId,
                "Posted feedback for '{$skill->skill_name}' offered by '{$teacherName}'"
            );
        }
        $_SESSION['feedback_success'] = $id ? "Feedback submitted." : "Failed to submit feedback.";
        header("Location: feedbacks.php");
        exit();
    }

    // ---------- EDIT FEEDBACK ----------
    if (isset($_POST['edit_feedback']) && $role === 'learner') {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $feedback_given = ($_POST['feedback_given'] ?? 'no') === 'yes' ? 'yes' : 'no';

        if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1-5.";

        $existing = Feedback::getById($conn, $feedback_id);
        if (!$existing || (int)$existing->users_user_id !== $currentUserId) {
            $errors[] = "You can only edit your own feedback.";
        }

        if ($existing && !isSkillCompleted($conn, $currentUserId, $existing->skills_skill_id)) {
            $errors[] = "Cannot edit feedback for incomplete skills.";
        }

        if ($errors) {
            $_SESSION['feedback_errors'] = $errors;
            $_SESSION['feedback_form_data'] = $_POST;
            header("Location: feedbacks.php?edit=$feedback_id");
            exit();
        }

        $ok = Feedback::update($conn, $feedback_id, [
            'rating' => $rating,
            'comment' => $comment,
            'feedback_given' => $feedback_given
        ]);

        if ($ok && $existing) {
            $skill = Skill::getById($conn, $existing->skills_skill_id);
            $teacherName = 'Unknown Teacher';

            $stmt = $conn->prepare("
                 SELECT u.user_name AS teacher_name
                 FROM user_skills us
                 JOIN users u ON us.user_id = u.user_id
                 WHERE us.skill_id = ?
                AND us.role = 'teacher'
                LIMIT 1
            ");
            $stmt->bind_param("i", $existing->skills_skill_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $teacherName = $row['teacher_name'] ?? 'Unknown Teacher';
            }
            $stmt->close();

            logActivity(
                $conn,
                $currentUserId,
                "Edited feedback for '{$skill->skill_name}' offered by '{$teacherName}'"
            );
        }

        $_SESSION['feedback_success'] = $ok ? "Feedback updated." : "Failed to update feedback.";
        header("Location: feedbacks.php");
        exit();
    }

    // ---------- DELETE FEEDBACK ----------
    if (isset($_POST['delete_feedback'])) {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $existing = Feedback::getById($conn, $feedback_id);
        if (!$existing) {
            $_SESSION['feedback_error'] = "Feedback not found.";
            header("Location: feedbacks.php");
            exit();
        }

        $allowed = ($role === 'admin') || ($role === 'learner' && (int)$existing->users_user_id === $currentUserId);
        if (!$allowed) {
            $_SESSION['feedback_error'] = "Not authorized.";
            header("Location: feedbacks.php");
            exit();
        }

        $ok = Feedback::delete($conn, $feedback_id);
        if ($ok && $existing) {
            $skill = Skill::getById($conn, $existing->skills_skill_id);
            $teacherName = 'Unknown Teacher';

            $stmt = $conn->prepare("
                 SELECT u.user_name AS teacher_name
                 FROM user_skills us
                 JOIN users u ON us.user_id = u.user_id
                 WHERE us.skill_id = ?
                AND us.role = 'teacher'
                LIMIT 1
            ");
            $stmt->bind_param("i", $existing->skills_skill_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $teacherName = $row['teacher_name'] ?? 'Unknown Teacher';
            }
            $stmt->close();

            logActivity(
                $conn,
                $currentUserId,
                "Deleted feedback for '{$skill->skill_name}' offered by '{$teacherName}'"
            );
        }
        $_SESSION['feedback_success'] = $ok ? "Feedback deleted." : "Failed to delete feedback.";
        header("Location: feedbacks.php");
        exit();
    }
}

// ===================== FETCH FEEDBACKS =====================
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC');

$feedbackRows = match ($role) {
    'admin' => Feedback::getAll($conn, $search, $sort),
    'teacher' => Feedback::getByTeacher($conn, $currentUserId),
    default => Feedback::getByUser($conn, $currentUserId),
};

// ===================== FEEDBACK FORM ELIGIBLE SKILLS =====================
$feedbackEligibleSkills = [];
if ($role === 'learner') {
    $learnerSkills = getLearnerEnrolledSkills($conn, $currentUserId);
    foreach ($learnerSkills as $skill) {
        if ($skill['status'] === 'completed' && !Feedback::existsForUserAndSkill($conn, $currentUserId, $skill['skill_id'])) {
            $feedbackEligibleSkills[] = $skill;
        }
    }
}

// Persist form data
$form_data = $_SESSION['feedback_form_data'] ?? [];
unset($_SESSION['feedback_form_data']);

include('../includes/layouts/header.php');
?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/feedbacks.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>
    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>
        <div class="main-content">
            <h2>Feedbacks</h2>

            <?php if (!empty($_SESSION['feedback_errors'])): ?>
                <div class="alert alert-error">
                    <?php foreach ($_SESSION['feedback_errors'] as $err) echo "<p>" . htmlspecialchars($err) . "</p>"; ?>
                </div>
                <?php unset($_SESSION['feedback_errors']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['feedback_success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['feedback_success']) ?></div>
                <?php unset($_SESSION['feedback_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['feedback_error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['feedback_error']) ?></div>
                <?php unset($_SESSION['feedback_error']); ?>
            <?php endif; ?>

            <!-- ---------- FEEDBACK FORM FOR LEARNERS ---------- -->
            <?php if ($role === 'learner' && !empty($feedbackEligibleSkills)): ?>
                <div class="feedback-form">
                   
                    <form method="POST">
                        <label for="skills_skill_id">Select Skill:</label>
                        <select name="skills_skill_id" required>
                            <?php foreach ($feedbackEligibleSkills as $skill): ?>
                                <option value="<?= $skill['skill_id'] ?>"><?= htmlspecialchars($skill['skill_name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="rating">Rating:</label>
                        <select name="rating" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>

                        <label for="comment">Comment:</label>
                        <textarea name="comment"><?= htmlspecialchars($form_data['comment'] ?? '') ?></textarea>

                        <input type="hidden" name="feedback_given" value="yes">
                        <button type="submit" name="add_feedback">Submit Feedback</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ---------- FEEDBACK TABLE ---------- -->
            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <?php if ($role === 'admin'): ?><th>ID</th><?php endif; ?>
                            <th>Learner</th>
                            <th>Skill</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Given</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($feedbackRows)): ?>
                            <?php foreach ($feedbackRows as $row):
                                $canEdit = ($role === 'learner' && $row->users_user_id == $currentUserId && isSkillCompleted($conn, $currentUserId, $row->skills_skill_id));
                                $canDelete = ($role === 'admin') || ($role === 'learner' && $row->users_user_id == $currentUserId);
                                $editing = isset($_GET['edit']) && $_GET['edit'] == $row->feedback_id;
                            ?>
                                <tr>
                                    <?php if ($role === 'admin'): ?>
                                        <td><?= htmlspecialchars($row->feedback_id) ?></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($row->user_name ?? '') ?></td>
                                    <td><?= htmlspecialchars($row->skill_name ?? '') ?></td>
                                    <td>
                                        <?php if ($canEdit && $editing): ?>
                                            <form method="POST" id="edit_form_<?= $row->feedback_id ?>">
                                                <input type="hidden" name="feedback_id" value="<?= $row->feedback_id ?>">
                                                <input type="hidden" name="feedback_given" value="yes">
                                                <select name="rating" required>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?= $i ?>" <?= $i == $row->rating ? 'selected' : '' ?>><?= $i ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="display-stars">
                                                    <?= str_repeat('★', $row->rating) . str_repeat('☆', 5 - $row->rating) ?>
                                                </span>
                                            <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canEdit && $editing): ?>
                                            <textarea name="comment" form="edit_form_<?= $row->feedback_id ?>" required><?= htmlspecialchars($row->comment) ?></textarea>
                                        <?php else: ?>
                                            <?= nl2br(htmlspecialchars($row->comment ?: '—')) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row->feedback_given) ?></td>
                                    <td><?= htmlspecialchars($row->created_at) ?></td>
                                    <td>
                                        <?php if ($canEdit && $editing): ?>
                                            <button type="submit" name="edit_feedback" form="edit_form_<?= $row->feedback_id ?>" class="action-link">💾 Save</button>
                                            </form>
                                        <?php elseif ($canEdit): ?>
                                            <a href="feedbacks.php?edit=<?= $row->feedback_id ?>" class="action-link" style="text-decoration:none">✏️ Edit</a>
                                        <?php endif; ?>

                                        <?php if ($canDelete): ?>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this feedback?');">
                                                <input type="hidden" name="feedback_id" value="<?= $row->feedback_id ?>">
                                                <button type="submit" name="delete_feedback" class="action-link delete" style="text-decoration: none;">🗑️ Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">No feedbacks found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>