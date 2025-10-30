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
    $user_name = trim($_POST['user_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $selected_skills = isset($_POST['skills']) ? array_map('intval', $_POST['skills']) : [];
    $profile_pic_path = $user['profile_pic'];

    // Validation
    if ($user_name === '') $errors[] = 'Full name is required.';
    if (count($selected_skills) < 2) $errors[] = 'Please select at least 2 skills.';

    // Profile picture upload
    if (!empty($_FILES['profile_pic']['name'])) {
        [$newPath, $err] = saveProfilePicture($_FILES['profile_pic'], $userId);
        if ($err) $errors[] = $err;
        else $profile_pic_path = $newPath;
    }

    if (empty($errors)) {
        updateUserProfileWithSkills($conn, $userId, $user_name, $bio, $selected_skills, $profile_pic_path);
        $success = true;
        $user = getUserById($conn, $userId); // Refresh user
    }
}

// Prepare display path for profile pic
$profilePic = !empty($user['profile_pic']) ? '/' . $user['profile_pic'] : null;
?>



<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/profile.css">

<main class="profile-page">

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="avatar-large">
            <?php if ($profilePic): ?>
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="avatar" class="profile-pic">
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
            <div class="section">
                <h3><?= $skillsLabel ?></h3>
                <ul class="skills-list">
                    <?php if (!empty($user['skills'])): ?>
                        <?php foreach ($user['skills'] as $sid):
                            $skillName = null;
                            foreach ($allSkills as $s) if ($s['skill_id'] == $sid) {
                                $skillName = $s['skill_name'];
                                break;
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

        <!-- Right Panel: Form -->
        <section class="profile-form panel">
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
                    <label for="user_name">Full Name</label>
                    <input id="user_name" name="user_name" type="text" value="<?= htmlspecialchars($user['user_name']) ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio"><?= htmlspecialchars($user['bio'] ?: '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="skills">Skills <small>(pick at least 2)</small></label>
                    <select id="skills" name="skills[]" multiple>
                        <?php foreach ($allSkills as $s): ?>
                            <option value="<?= $s['skill_id'] ?>" <?= in_array($s['skill_id'], $user['skills']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['skill_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?= htmlspecialchars($roleLabel) ?>" readonly>
                </div> -->

                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn primary">Update Profile</button>
                </div>
            </form>
        </section>
    </div>
</main>

<script>
    // Live preview for profile picture
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

    // Ensure at least 2 skills selected
    document.getElementById('profileForm').addEventListener('submit', e => {
        const selected = [...document.getElementById('skills').options].filter(o => o.selected);
        if (selected.length < 2) {
            e.preventDefault();
            alert('Please select at least 2 skills.');
        }
    });
</script>