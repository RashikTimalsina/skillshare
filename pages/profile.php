<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../middlewares/session_check.php';
require_once __DIR__ . '/../includes/layouts/header.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'learner';
$user = getUserById($conn, $userId);
$allSkills = getAllSkills($conn);

$isLearner = strtolower($userRole) === 'learner';
$roleLabel = $isLearner ? 'Learner' : ($userRole === 'teacher' ? 'Teacher' : 'Admin');
$skillsLabel = $isLearner ? 'Skills you learn' : 'Skills you teach';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $selected_skills = isset($_POST['skills']) ? array_map('intval', $_POST['skills']) : [];
    $profile_pic_path = $user['profile_pic'];

    // Validation
    if ($full_name === '') $errors[] = 'Full name is required.';
    if (count($selected_skills) < 2) $errors[] = 'Please select at least 2 skills.';

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profile_pic']['tmp_name'];
        $fileName = basename($_FILES['profile_pic']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($fileExt, $allowed)) {
            $errors[] = 'Only JPG, JPEG, PNG, GIF, or WEBP images are allowed.';
        } else {
            $newFileName = 'uploads/profile_pics/' . $userId . '_' . time() . '.' . $fileExt;
            if (!is_dir('uploads/profile_pics')) mkdir('uploads/profile_pics', 0755, true);
            if (move_uploaded_file($fileTmp, __DIR__ . '/../' . $newFileName)) {
                $profile_pic_path = $newFileName;
            } else {
                $errors[] = 'Failed to upload the profile picture.';
            }
        }
    }

    if (empty($errors)) {
        updateUserProfileWithSkills($conn, $userId, $full_name, $bio, $selected_skills, $profile_pic_path);
        $success = true;
        $user = getUserById($conn, $userId);
    }
}
?>

<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/profile.css">

<main class="profile-page">

    <!-- HEADER -->
    <div class="profile-header">
        <div class="avatar-large">
            <?php if (!empty($user['profile_pic'])): ?>
                <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="avatar">
            <?php else: ?>
                <div class="avatar-placeholder"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div class="profile-summary">
            <h1><?= htmlspecialchars($user['full_name']) ?></h1>
            <p class="role"><?= htmlspecialchars($roleLabel) ?></p>
            <p class="joined-date">Joined <?= htmlspecialchars(date('F j, Y', strtotime($user['created_at']))) ?></p>
            <button id="toggleEditForm" class="btn edit-btn">Edit Profile</button>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="profile-content">

        <!-- LEFT: Skills & Bio -->
        <aside class="profile-card">
            <div class="section">
                <h3><?= $skillsLabel ?></h3>
                <ul class="skills-list">
                    <?php if (!empty($user['skills'])): ?>
                        <?php foreach ($user['skills'] as $sid):
                            $skillName = null;
                            foreach ($allSkills as $s) {
                                if ($s['skill_id'] == $sid) {
                                    $skillName = $s['skill_name'];
                                    break;
                                }
                            }
                        ?>
                            <li><?= htmlspecialchars($skillName ?? '—') ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>—</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="section">
                <h3>About / Bio</h3>
                <p><?= !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'Not added yet' ?></p>
            </div>
        </aside>

        <!-- RIGHT: Form -->
        <section id="profileFormSection" class="profile-form panel">
            <h2>Update Your Profile</h2>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">Profile updated successfully.</div>
            <?php endif; ?>

            <form method="post" id="profileForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" disabled>
                </div>

                <fieldset class="role-field">
                    <legend>Role</legend>
                    <label class="radio-inline">
                        <input type="radio" name="role" value="learner" <?= $isLearner ? 'checked' : '' ?> disabled> Learner
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="role" value="teacher" <?= !$isLearner && $userRole === 'teacher' ? 'checked' : '' ?> disabled> Teacher
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="role" value="admin" <?= $userRole === 'admin' ? 'checked' : '' ?> disabled> Admin
                    </label>
                </fieldset>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" readonly><?= htmlspecialchars($user['bio'] ?: 'Not added yet') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="skills">Skills <small>(pick at least 2)</small></label>
                    <select id="skills" name="skills[]" multiple disabled>
                        <?php foreach ($allSkills as $s): ?>
                            <option value="<?= $s['skill_id'] ?>" <?= in_array($s['skill_id'], $user['skills']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['skill_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn primary" disabled>Update Profile</button>
                </div>
            </form>
        </section>

    </div>
</main>

<script>
    // Toggle edit form
    document.getElementById('toggleEditForm').addEventListener('click', function() {
        const form = document.getElementById('profileFormSection');
        const inputs = form.querySelectorAll('input, textarea, select, button');
        inputs.forEach(el => {
            if (el.tagName !== 'BUTTON' || el.name === 'update_profile') {
                el.readOnly = !el.readOnly;
                el.disabled = !el.disabled;
            }
        });
        form.querySelector('button[name="update_profile"]').disabled = !form.querySelector('button[name="update_profile"]').disabled;
    });

    // Profile picture preview
    const avatarImg = document.querySelector('.avatar-large img');
    const avatarPlaceholder = document.querySelector('.avatar-placeholder');

    document.getElementById('profile_pic')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                if (avatarImg) {
                    avatarImg.src = ev.target.result;
                } else if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.style.width = '130px';
                    img.style.height = '130px';
                    img.style.borderRadius = '50%';
                    img.style.objectFit = 'cover';
                    img.style.border = '3px solid #ccc';
                    document.querySelector('.avatar-large').appendChild(img);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Minimum 2 skills validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const selected = [...document.getElementById('skills').options].filter(o => o.selected);
        if (selected.length < 2) {
            e.preventDefault();
            alert('Please select at least 2 skills.');
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/layouts/footer.php'; ?>