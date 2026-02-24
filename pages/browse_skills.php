<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layouts/header.php';

/* Access control */
$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;

// --- Handle enrollment POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skill_id']) && $userId) {
    if ($userRole !== 'learner') {
        $_SESSION['error'] = 'Only learners can enroll in skills.';
        header('Location: ../unauthorized.php');
        exit;
    }

    $skillId = (int) $_POST['skill_id'];

    // Check if already enrolled
    $check = $conn->prepare("SELECT 1 FROM enrollments WHERE users_user_id = ? AND skills_skill_id = ?");
    $check->bind_param("ii", $userId, $skillId);
    $check->execute();

    if ($check->get_result()->num_rows === 0) {
        // Insert enrollment
        $stmt = $conn->prepare("INSERT INTO enrollments (users_user_id, skills_skill_id, status, enrolled_at) VALUES (?, ?, 'enrolled', NOW())");
        $stmt->bind_param("ii", $userId, $skillId);
        $stmt->execute();
        $stmt->close();

        // Fetch skill details for activity log
        require_once __DIR__ . '/../includes/flash_messages.php';
        require_once __DIR__ . '/../models/Skill.php';
        $skill = Skill::getById($conn, $skillId);

        if ($skill) {
            // Fetch teacher name for this skill
            $stmt = $conn->prepare("
        SELECT u.user_name AS teacher_name
        FROM teachers t
        JOIN users u ON t.users_user_id = u.user_id
        WHERE t.skills_skill_id = ?
        AND t.status = 'active'
        LIMIT 1
    ");
            $stmt->bind_param("i", $skillId);
            $stmt->execute();
            $result = $stmt->get_result();
            $teacherData = $result->fetch_assoc();
            $stmt->close();

            $teacherName = $teacherData['teacher_name'] ?? 'Unknown Teacher';

            logActivity(
                $conn,
                $userId,
                "Enrolled in skill '{$skill->skill_name}' offered by '{$teacherName}'"
            );
        }

        setFlash('enrollments', 'success', 'Successfully enrolled in the skill!');
    } else {
        require_once __DIR__ . '/../includes/flash_messages.php';
        setFlash('enrollments', 'warning', 'You are already enrolled in this skill.');
    }
    $check->close();
    header('Location: enrollments.php');
    exit;
}

/* Handle search query */
$searchQuery = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchQuery = trim($_GET['search']);
}

$sql = "
    SELECT 
        s.skill_id, s.skill_name, s.description, s.thumbnail,
        u.user_name AS teacher_name
    FROM skills s
    JOIN teachers t ON t.skills_skill_id = s.skill_id AND t.status = 'active'
    JOIN users u ON u.user_id = t.users_user_id
    " . ($searchQuery ? "WHERE s.skill_name LIKE ?" : "") . "
    ORDER BY s.created_at DESC
";

if ($searchQuery) {
    $stmt = $conn->prepare($sql);
    $likeQuery = "{$searchQuery}%";
    $stmt->bind_param("s", $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $skills = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $skills = [];
    if ($result = mysqli_query($conn, $sql)) {
        $skills = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    }
}

/* Fetch enrolled skills for learners */
$enrolledSkills = [];
if ($userId && $userRole === 'learner') {
    $stmt = $conn->prepare("SELECT skills_skill_id FROM enrollments WHERE users_user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $enrolledSkills = array_column($rows, 'skills_skill_id');
    $stmt->close();
}

/* Determine back URL */
if (!$userId) {
    $backUrl = '../index.php'; // guests go back to homepage
} elseif ($userRole === 'admin') {
    $backUrl = '../dashboard/admin_dashboard.php';
} else { // default learner
    $backUrl = '../dashboard/learner_dashboard.php';
}
?>

<link rel="stylesheet" href="../assets/css/pages/browse_skills.css">

<div class="browse-skills-container">
    <!-- Header row: Back | Title | Search -->
    <div class="browse-header">
        <div class="back-icon">
            <a href="<?= $backUrl ?>" title="Back to Dashboard">&#8592; Back</a>
        </div>
        <h2 class="browse-skills-title">Explore Skills</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search skills..." value="<?= htmlspecialchars($searchQuery) ?>">
            <?php if ($searchQuery): ?>
                <a href="browse_skills.php" class="clear-search" title="Clear Search">&times;</a>
            <?php else: ?>
                <button type="submit">Search</button>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($skills)): ?>
        <p class="no-skills">
            <?php if ($searchQuery): ?>
                No skills found for "<?= htmlspecialchars($searchQuery) ?>".
            <?php else: ?>
                No skills available yet. Check back later.
            <?php endif; ?>
        </p>
    <?php else: ?>
        <div class="skills-grid <?= $searchQuery ? 'search-results' : '' ?>">
            <?php foreach ($skills as $skill): ?>
                <?php
                $thumbPath = !empty($skill['thumbnail']) ? "../" . htmlspecialchars($skill['thumbnail']) : "../uploads/skills/skill_placeholder.png";
                ?>
                <div class="skill-card">
                    <a href="<?= $thumbPath ?>" target="_blank" class="thumb">
                        <img src="<?= $thumbPath ?>" alt="Thumbnail of <?= htmlspecialchars($skill['skill_name']) ?>">
                    </a>
                    <div class="meta">
                        <strong><?= htmlspecialchars($skill['skill_name']) ?></strong>
                        <p class="teacher-name">Offered by: <?= htmlspecialchars($skill['teacher_name']) ?></p>
                        <p class="description"><?= htmlspecialchars($skill['description'] ?: 'No description available.') ?></p>
                    </div>

                    <?php if ($userId && $userRole === 'learner'): ?>
                        <?php if (in_array($skill['skill_id'], $enrolledSkills)): ?>
                            <button class="enroll-btn enrolled" disabled>Already Enrolled</button>
                        <?php else: ?>
                            <form method="POST" class="enroll-form">
                                <input type="hidden" name="skill_id" value="<?= $skill['skill_id'] ?>">
                                <button type="submit" class="enroll-btn">Enroll Now</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($userId): ?>
                        <span class="badge"></span>
                    <?php else: ?>
                        <a href="../auth/login.php" class="enroll-btn" style="text-decoration: none;">Login to Enroll</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>