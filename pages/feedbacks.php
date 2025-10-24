<?php
session_start();
include('../includes/functions.php');
include('../includes/config.php');
include('../middlewares/session_check.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_role'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['user_role'];

// --- Handle Add Feedback ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_feedback'])) {
    $user_id = $_POST['users_user_id'];
    $skill_id = $_POST['skills_skill_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $feedback_given = $_POST['feedback_given'];

    $errors = [];

    // Validate User
    $stmtUser = $conn->prepare("SELECT user_id FROM users WHERE user_id=?");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    if ($stmtUser->get_result()->num_rows === 0) {
        $errors[] = "User ID $user_id not found!";
    }

    // Validate Skill
    $stmtSkill = $conn->prepare("SELECT skill_id FROM skills WHERE skill_id=?");
    $stmtSkill->bind_param("i", $skill_id);
    $stmtSkill->execute();
    if ($stmtSkill->get_result()->num_rows === 0) {
        $errors[] = "Skill ID $skill_id not found!";
    }

    // If errors, save in session and redirect
    if (!empty($errors)) {
        $_SESSION['feedback_errors'] = $errors;
        $_SESSION['feedback_form_data'] = $_POST;
        header("Location: feedbacks.php");
        exit();
    }

    // Insert Feedback
    $stmt = $conn->prepare("INSERT INTO feedback (users_user_id, skills_skill_id, rating, comment, feedback_given, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iidss", $user_id, $skill_id, $rating, $comment, $feedback_given);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Added new feedback (User ID: $user_id, Skill ID: $skill_id)");

    $_SESSION['feedback_success'] = "Feedback added successfully!";
    header("Location: feedbacks.php");
    exit();
}

// --- Handle Edit Feedback ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_feedback'])) {
    $feedback_id = $_POST['feedback_id'];
    $user_id = $_POST['users_user_id'];
    $skill_id = $_POST['skills_skill_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $feedback_given = $_POST['feedback_given'];

    $errors = [];

    // Validate User
    $stmtUser = $conn->prepare("SELECT user_id FROM users WHERE user_id=?");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    if ($stmtUser->get_result()->num_rows === 0) {
        $errors[] = "User ID $user_id not found!";
    }

    // Validate Skill
    $stmtSkill = $conn->prepare("SELECT skill_id FROM skills WHERE skill_id=?");
    $stmtSkill->bind_param("i", $skill_id);
    $stmtSkill->execute();
    if ($stmtSkill->get_result()->num_rows === 0) {
        $errors[] = "Skill ID $skill_id not found!";
    }

    if (!empty($errors)) {
        $_SESSION['feedback_errors'] = $errors;
        $_SESSION['feedback_form_data'] = $_POST;
        header("Location: feedbacks.php?edit=$feedback_id");
        exit();
    }

    // Update Feedback
    $stmt = $conn->prepare("UPDATE feedback SET users_user_id=?, skills_skill_id=?, rating=?, comment=?, feedback_given=? WHERE feedback_id=?");
    $stmt->bind_param("iidssi", $user_id, $skill_id, $rating, $comment, $feedback_given, $feedback_id);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Edited feedback ID $feedback_id");
    $_SESSION['feedback_success'] = "Feedback updated successfully!";
    header("Location: feedbacks.php");
    exit();
}

// --- Handle Delete Feedback ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback'])) {
    $feedback_id = $_POST['feedback_id'];
    $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id=?");
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();

    logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], "Deleted feedback ID $feedback_id");
    $_SESSION['feedback_success'] = "Feedback deleted successfully!";
    header("Location: feedbacks.php");
    exit();
}

// --- Search & Sort ---
$search = $_GET['search'] ?? '';
$sort = strtoupper($_GET['sort'] ?? 'ASC');
$sort = $sort === 'DESC' ? 'DESC' : 'ASC';

$sql = "SELECT f.feedback_id, f.users_user_id, f.skills_skill_id, f.rating, f.comment, f.feedback_given, f.created_at
        FROM feedback f
        WHERE CAST(f.feedback_id AS CHAR) LIKE ? 
           OR CAST(f.users_user_id AS CHAR) LIKE ? 
           OR CAST(f.skills_skill_id AS CHAR) LIKE ?
        ORDER BY f.feedback_id $sort";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// Editing
$edit_feedback_id = $_GET['edit'] ?? null;

// Load form data if previously submitted with errors
$form_data = $_SESSION['feedback_form_data'] ?? [];
unset($_SESSION['feedback_form_data']);
?>

<?php include('../includes/layouts/header.php'); ?>
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/feedbacks.css">

<div class="layout-wrapper">
    <?php include('../includes/layouts/sidebar.php'); ?>

    <div class="main-content-wrapper">
        <?php include('../includes/layouts/topbar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h2>Feedback Management</h2>
            </div>

            <!-- Display Errors / Success -->
            <?php if (!empty($_SESSION['feedback_errors'])): ?>
                <div class="error-messages">
                    <?php foreach ($_SESSION['feedback_errors'] as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['feedback_errors']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['feedback_success'])): ?>
                <div class="success-message">
                    <p><?= htmlspecialchars($_SESSION['feedback_success']) ?></p>
                </div>
                <?php unset($_SESSION['feedback_success']); ?>
            <?php endif; ?>

            <div class="search-sort-container">
                <form method="GET" class="search-sort-form">
                    <input class="search-input" type="text" name="search" placeholder="Search by Feedback ID, User ID, or Skill ID" value="<?= htmlspecialchars($search) ?>">
                    <select class="sort-select" name="sort">
                        <option value="ASC" <?= $sort === 'ASC' ? 'selected' : '' ?>>1 →</option>
                        <option value="DESC" <?= $sort === 'DESC' ? 'selected' : '' ?>><- 1</option>
                    </select>
                    <button class="search-btn" type="submit">Apply</button>
                </form>
            </div>

            <?php if ($role === 'admin'): ?><form method="POST"><?php endif; ?>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Skill ID</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Feedback Given</th>
                                <th>Created At</th>
                                <?php if ($role === 'admin') echo '<th>Actions</th>'; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['feedback_id'] ?></td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_feedback_id == $row['feedback_id']): ?>
                                                <input type="number" name="users_user_id" value="<?= htmlspecialchars($form_data['users_user_id'] ?? $row['users_user_id']) ?>" required>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['users_user_id']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_feedback_id == $row['feedback_id']): ?>
                                                <input type="number" name="skills_skill_id" value="<?= htmlspecialchars($form_data['skills_skill_id'] ?? $row['skills_skill_id']) ?>" required>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['skills_skill_id']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_feedback_id == $row['feedback_id']): ?>
                                                <div class="star-rating">
                                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" id="star<?= $i ?>_<?= $row['feedback_id'] ?>" name="rating" value="<?= $i ?>" <?= ($row['rating'] == $i) ? 'checked' : '' ?>>
                                                        <label for="star<?= $i ?>_<?= $row['feedback_id'] ?>">&#9733;</label>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="display-stars">
                                                    <?php
                                                    $filled = floor($row['rating']);
                                                    $half = ($row['rating'] - $filled) >= 0.5;
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $filled) echo '★';
                                                        elseif ($half && $i == $filled + 1) echo '☆';
                                                        else echo '☆';
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_feedback_id == $row['feedback_id']): ?>
                                                <textarea name="comment"><?= htmlspecialchars($form_data['comment'] ?? $row['comment']) ?></textarea>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['comment'] ?? '—') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($role === 'admin' && $edit_feedback_id == $row['feedback_id']): ?>
                                                <select name="feedback_given">
                                                    <option value="yes" <?= ($row['feedback_given'] === 'yes') ? 'selected' : '' ?>>Yes</option>
                                                    <option value="no" <?= ($row['feedback_given'] === 'no') ? 'selected' : '' ?>>No</option>
                                                </select>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['feedback_given']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['created_at']) ?></td>

                                        <?php if ($role === 'admin'): ?>
                                            <td>
                                                <?php if ($edit_feedback_id == $row['feedback_id']): ?>
                                                    <input type="hidden" name="feedback_id" value="<?= $row['feedback_id'] ?>">
                                                    <button type="submit" name="edit_feedback" class="action-link">Save</button>
                                                    <a href="feedbacks.php" class="action-link delete">Cancel</a>
                                                <?php else: ?>
                                                    <a href="?edit=<?= $row['feedback_id'] ?>" class="action-link">✏️ Edit</a>
                                                    <form method="POST" class="delete-feedback-form" style="display:inline-block;">
                                                        <input type="hidden" name="feedback_id" value="<?= $row['feedback_id'] ?>">
                                                        <button type="submit" name="delete_feedback" class="action-link delete">🗑️ Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $role === 'admin' ? 8 : 7 ?>" style="text-align:center;">No feedbacks found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($role === 'admin'): ?>
                </form>
                <div class="bottom-buttons">
                    <button id="addFeedbackBtn" class="add-user-btn">+ Add Feedback</button>
                </div>

                <script>
                    const addBtn = document.getElementById('addFeedbackBtn');
                    const tbody = document.querySelector('.users-table tbody');
                    addBtn.addEventListener('click', () => {
                        if (document.getElementById('newFeedbackRow')) return;
                        const newRow = document.createElement('tr');
                        newRow.id = 'newFeedbackRow';
                        newRow.innerHTML = `
                        <td>New</td>
                        <td><input type="number" name="users_user_id" placeholder="User ID" required></td>
                        <td><input type="number" name="skills_skill_id" placeholder="Skill ID" required></td>
                        <td>
                            <div class="star-rating">
                                <input type="radio" id="star5_new" name="rating" value="5"><label for="star5_new">&#9733;</label>
                                <input type="radio" id="star4_new" name="rating" value="4"><label for="star4_new">&#9733;</label>
                                <input type="radio" id="star3_new" name="rating" value="3"><label for="star3_new">&#9733;</label>
                                <input type="radio" id="star2_new" name="rating" value="2"><label for="star2_new">&#9733;</label>
                                <input type="radio" id="star1_new" name="rating" value="1"><label for="star1_new">&#9733;</label>
                            </div>
                        </td>
                        <td><textarea name="comment" placeholder="Comment"></textarea></td>
                        <td>
                            <select name="feedback_given">
                                <option value="yes">Yes</option>
                                <option value="no" selected>No</option>
                            </select>
                        </td>
                        <td>Auto</td>
                        <td>
                            <button type="submit" name="add_feedback" class="action-link">Save</button>
                            <button type="button" id="cancelNewFeedback" class="action-link delete">Cancel</button>
                        </td>`;
                        tbody.appendChild(newRow);
                        document.getElementById('cancelNewFeedback').addEventListener('click', () => newRow.remove());
                    });

                    document.addEventListener('click', e => {
                        if (e.target.matches('.delete-feedback-form button, .delete-feedback-form button *')) {
                            e.preventDefault();
                            if (confirm("⚠️ Are you sure you want to delete this feedback?")) {
                                e.target.closest('form').submit();
                            }
                        }
                    });

                    const hamburger = document.getElementById('hamburger-btn');
                    const sidebar = document.querySelector('.sidebar');
                    hamburger.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/layouts/footer.php'); ?>