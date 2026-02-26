<!-- =================PROFILE MANAGEMENT PAGE======================= -->
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../middlewares/session_check.php';
require_once __DIR__ . '/../includes/layouts/header.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'learner';
$user = getUserById($conn, $userId);
$allSkills = getAllSkills($conn);

$isLearner = strtolower($userRole) === 'learner';
$isAdmin = strtolower($userRole) === 'admin';
$roleLabel = $isLearner ? 'Learner' : ($userRole === 'teacher' ? 'Teacher' : 'Admin');
$skillsLabel = $isLearner ? 'Skills you learn :' : 'Skills you offer :';

$errors = [];
$success = false;

// ===============================
// Handle profile update
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_name = trim($_POST['user_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $profile_pic_path = $user['profile_pic'];

    if ($user_name === '') $errors[] = 'Full name is required.';

    if (!empty($_FILES['profile_pic']['name'])) {
        [$newPath, $err] = saveProfilePicture($_FILES['profile_pic'], $userId);
        if ($err) $errors[] = $err;
        else $profile_pic_path = $newPath;
    }

    if (empty($errors)) {

        $hasChanges = false;

        if ($user_name !== $user['user_name']) $hasChanges = true;
        if ($bio !== $user['bio']) $hasChanges = true;
        if ($profile_pic_path !== $user['profile_pic']) $hasChanges = true;
        $originalUser = $user;

        if ($hasChanges) {
            updateUserProfileWithSkills($conn, $userId, $user_name, $bio, [], $profile_pic_path);
            $success = true;
            $user = getUserById($conn, $userId);

            // Activity log
            $activityDetails = "Updated profile";
            $changedFields = [];
            if ($user_name !== $originalUser['user_name']) $changedFields[] = "Name";
            if ($bio !== $originalUser['bio']) $changedFields[] = "Bio";
            if ($profile_pic_path !== $originalUser['profile_pic']) $changedFields[] = "Profile Picture";

            if (!empty($changedFields)) {
                $activityDetails .= " - Changed: " . implode(", ", $changedFields);
            }

            logActivity($conn, $userId, $activityDetails);
        }
    }
}

// ===============================
// Fetch learner / teacher skills
// ===============================
$profileSkills = [];

if (!$isAdmin) {
    if ($isLearner) {
        // Fetch learner's unique active skills
        $sql = "
            SELECT s.skill_id, s.skill_name, GROUP_CONCAT(DISTINCT u.user_name SEPARATOR ', ') AS teachers
            FROM enrollments e
            JOIN skills s ON e.skills_skill_id = s.skill_id
            LEFT JOIN teachers t ON t.skills_skill_id = s.skill_id AND t.status='active'
            LEFT JOIN users u ON u.user_id = t.users_user_id
            WHERE e.users_user_id = ? AND e.status IN ('enrolled','completed')
            GROUP BY s.skill_name
            ORDER BY s.skill_name ASC
        ";
    } else {
        // Teacher's active teaching skills
        $sql = "
            SELECT s.skill_id, s.skill_name
            FROM teachers t
            JOIN skills s ON t.skills_skill_id = s.skill_id
            WHERE t.users_user_id = ? AND t.status='active'
            GROUP BY s.skill_name
            ORDER BY s.skill_name ASC
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profileSkills = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$profilePic = !empty($user['profile_pic']) ? '/' . $user['profile_pic'] : null;
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/pages/profile.css">

<main class="profile-page">

    <!-- Profile Header -->
    <div class="profile-header">

        <a href="<?= $isAdmin
                        ? '../dashboard/admin_dashboard.php'
                        : ($isLearner
                            ? '../dashboard/learner_dashboard.php'
                            : '../dashboard/teacher_dashboard.php') ?>"
            class="back-btn-header">
            &#8592;
        </a>


        <div class="avatar-large">
            <?php if ($profilePic): ?>
                <img src="<?= "../" . htmlspecialchars($user['profile_pic']) ?>" alt="avatar" class="profile-pic">
            <?php else: ?>
                <div class="avatar-placeholder"><?= strtoupper(substr($user['user_name'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div class="profile-summary">
            <h1><?= htmlspecialchars($user['user_name']) ?></h1>
            <p class="role"><?= htmlspecialchars($roleLabel) ?></p>
            <p class="joined-date">Joined <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
        </div>
    </div>

    <div class="profile-content">
        <!-- Left Panel -->
        <aside class="profile-card">
            <?php if (!$isAdmin): ?>
                <div class="section">
                    <h3><?= $skillsLabel ?></h3>
                    <ul class="skills-list">
                        <?php if (!empty($profileSkills)): ?>
                            <?php foreach ($profileSkills as $skill): ?>
                                <li>
                                    <?= htmlspecialchars($skill['skill_name']) ?>
                                    <?php if (!empty($skill['teachers'])): ?>
                                        <!-- <span class="skill-teachers">(<?= htmlspecialchars($skill['teachers']) ?>)</span> -->
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>—</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="section">
                <h3>About / Bio : </h3>
                <p><?= !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'Not added yet' ?></p>
            </div>
        </aside>

        <!-- Right Panel: Form -->
        <section class="profile-form panel">
            <h2>Profile Details</h2>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">Profile updated successfully.</div>
            <?php endif; ?>

            <form method="post" id="profileForm" enctype="multipart/form-data" class="view-mode">
                <div class="form-group">
                    <label for="user_name">Full Name</label>
                    <input id="user_name" name="user_name" type="text" value="<?= htmlspecialchars($user['user_name']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" disabled>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" readonly><?= htmlspecialchars($user['bio'] ?: '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" id="editProfileBtn" class="btn secondary">Edit Profile</button>
                    <button type="submit" name="update_profile" class="btn primary" style="display:none;">Update Profile</button>
                </div>
            </form>
        </section>
    </div>
</main>

<script>
    const profileForm = document.getElementById('profileForm');
    const editBtn = document.getElementById('editProfileBtn');
    const updateBtn = profileForm.querySelector('button[name="update_profile"]');

    editBtn.addEventListener('click', () => {
        profileForm.querySelectorAll('input, textarea').forEach(el => el.removeAttribute('readonly'));
        profileForm.querySelector('input[type="file"]').removeAttribute('disabled');
        editBtn.style.display = 'none';
        updateBtn.style.display = 'inline-block';
    });

    // Profile picture live preview
    const avatarWrapper = document.querySelector('.avatar-large');
    const avatarImg = avatarWrapper.querySelector('img.profile-pic');
    const avatarPlaceholder = avatarWrapper.querySelector('.avatar-placeholder');

    document.getElementById('profile_pic')?.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => {
            if (avatarImg) avatarImg.src = ev.target.result;
            else {
                if (avatarPlaceholder) avatarPlaceholder.style.display = 'none';
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.className = 'profile-pic';
                avatarWrapper.appendChild(img);
            }
        };
        reader.readAsDataURL(file);
    });
</script>