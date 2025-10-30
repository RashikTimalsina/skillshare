<?php
// ==========================================
// pages/browse_skills.php
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/layouts/header.php';

// ==========================================
// FETCH ALL SKILLS (Newest first)
// ==========================================
$skills = [];
$query = "SELECT skill_id, skill_name, description FROM skills ORDER BY created_at DESC";
if ($result = mysqli_query($conn, $query)) {
    $skills = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
}

// ==========================================
// HANDLE ENROLLMENT FORM SUBMISSION
// ==========================================
if (isset($_POST['skill_id']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $skill_id = $_POST['skill_id'];

    // Check if already enrolled
    $check = $conn->prepare("SELECT * FROM enrollments WHERE users_user_id=? AND skills_skill_id=?");
    $check->bind_param("ii", $user_id, $skill_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO enrollments (users_user_id, skills_skill_id, status, enrolled_at) VALUES (?, ?, 'enrolled', NOW())");
        $stmt->bind_param("ii", $user_id, $skill_id);
        $stmt->execute();
    }

    // Redirect to enrollments page after enrolling
    header("Location: ../pages/enrollments.php");
    exit;
}
?>

<!-- ======================= STYLES ======================= -->
<link rel="stylesheet" href="../assets/css/browse_skills.css">

<!-- ======================= MAIN CONTENT ======================= -->
<div class="browse-skills-container">
    <h2 class="browse-skills-title">Explore Skills</h2>

    <div id="skillsGrid" class="skills-grid">
        <?php if (empty($skills)): ?>
            <p class="no-skills">No skills available yet. Check back later.</p>
        <?php else: ?>
            <?php foreach ($skills as $skill): ?>
                <div class="skill-card skill-item">
                    <div class="thumb">Thumbnail</div>
                    <div class="meta">
                        <strong><?= htmlspecialchars($skill['skill_name']) ?></strong>
                        <p><?= htmlspecialchars($skill['description']) ?></p>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" class="enroll-form">
                            <input type="hidden" name="skill_id" value="<?= $skill['skill_id'] ?>">
                            <button type="submit" class="enroll-btn">Enroll Now</button>
                        </form>
                    <?php else: ?>
                        <a href="../auth/signup.php" class="enroll-btn">Sign up to Enroll</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (count($skills) > 8): ?>
        <div class="show-more-wrap">
            <button id="showMoreBtn" class="show-more-btn">Show more ↓</button>
        </div>
    <?php endif; ?>
</div>

<!-- ======================= SCRIPT ======================= -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ITEMS_TO_SHOW = 8;
        const cards = [...document.querySelectorAll('.skill-item')];
        const btn = document.getElementById('showMoreBtn');

        const showLimited = () => {
            cards.forEach((c, i) => c.style.display = i < ITEMS_TO_SHOW ? '' : 'none');
            if (btn) btn.textContent = 'Show more ↓';
        };

        const showAll = () => {
            cards.forEach(c => c.style.display = '');
            if (btn) btn.textContent = 'Show less ↑';
        };

        if (btn) {
            showLimited();
            btn.addEventListener('click', () => {
                const hidden = cards.some(c => c.style.display === 'none');
                hidden ? showAll() : showLimited();
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/layouts/footer.php'; ?>