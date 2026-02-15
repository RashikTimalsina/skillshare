<?php
// ======================= SESSION & ROLE HELPERS =======================

// Admin logging (unchanged)
function logActivity(mysqli $conn, int $userId, string $action): void
{
    // Guard: ensure the user exists
    $check = $conn->prepare("SELECT 1 FROM users WHERE user_id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $check->close();
        return; // silently ignore invalid user_id
    }
    $check->close();

    // Insert activity
    $stmt = $conn->prepare("
        INSERT INTO activities (user_id, action, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    $stmt->close();
}


// Learner logging
function logLearnerActivity(mysqli $conn, int $learnerId, string $action, ?int $skillId = null, ?string $context = null): void
{
    // Guard: ensure learner exists
    $check = $conn->prepare("SELECT 1 FROM users WHERE user_id=? AND role='learner'");
    $check->bind_param("i", $learnerId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        return;
    }
    $check->close();

    // Optional skill context
    $skillName = null;
    if ($skillId) {
        $stmt = $conn->prepare("SELECT skill_name FROM skills WHERE skill_id=?");
        $stmt->bind_param("i", $skillId);
        $stmt->execute();
        $stmt->bind_result($skillName);
        $stmt->fetch();
        $stmt->close();
    }

    $fullAction = $action;
    if ($skillName) $fullAction .= " (Skill: $skillName)";
    if ($context) $fullAction .= " - $context";

    $stmt = $conn->prepare("INSERT INTO activities (user_id, action, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $learnerId, $fullAction);
    $stmt->execute();
    $stmt->close();
}

// Teacher logging
function logTeacherActivity(mysqli $conn, int $teacherId, string $action, ?int $skillId = null, ?string $context = null): void
{
    // Guard: ensure teacher exists
    $check = $conn->prepare("SELECT 1 FROM users WHERE user_id=? AND role='teacher'");
    $check->bind_param("i", $teacherId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        return;
    }
    $check->close();

    // Optional skill context
    $skillName = null;
    if ($skillId) {
        $stmt = $conn->prepare("SELECT skill_name FROM skills WHERE skill_id=?");
        $stmt->bind_param("i", $skillId);
        $stmt->execute();
        $stmt->bind_result($skillName);
        $stmt->fetch();
        $stmt->close();
    }

    $fullAction = $action;
    if ($skillName) $fullAction .= " (Skill: $skillName)";
    if ($context) $fullAction .= " - $context";

    $stmt = $conn->prepare("INSERT INTO activities (user_id, action, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $teacherId, $fullAction);
    $stmt->execute();
    $stmt->close();
}



// Fetch recent activities
function getRecentActivities(mysqli $conn, string $userRole, int $userId, int $limit = 5): array
{
    if ($userRole === 'admin') {
        $stmt = $conn->prepare("
            SELECT 
                a.action,
                a.created_at,
                COALESCE(u.user_name, 'Unknown') AS username
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.user_id
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                a.action,
                a.created_at,
                COALESCE(u.user_name, 'Unknown') AS username
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $result;
}


function getAdminStats($conn)
{
    return [
        'activeUsers' => $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0],
        'completedCourses' => $conn->query("SELECT COUNT(*) FROM enrollments WHERE status='completed'")->fetch_row()[0],
        'pendingFeedbacks' => $conn->query("SELECT COUNT(*) FROM feedback WHERE feedback_given='no'")->fetch_row()[0],
        'newRegistrations' => $conn->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_row()[0]
    ];
}

function getSingleValue($conn, $query) {
    $res = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($res)['COUNT(*)'] ?? 0;
}



// ======================= USER FUNCTIONS =======================

function getUserById($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT u.user_id, u.user_name, u.email, u.role, u.bio, u.profile_pic, u.created_at,
               GROUP_CONCAT(us.skill_id) AS skills
        FROM users u
        LEFT JOIN user_skills us ON u.user_id = us.user_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $user['skills'] = $user['skills'] ? array_map('intval', explode(',', $user['skills'])) : [];
    }

    return $user ?: null;
}

function getAllSkills($conn)
{
    $result = $conn->query("SELECT skill_id, skill_name FROM skills ORDER BY skill_name ASC");
    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row;
    }
    return $skills;
}

function updateUserProfileWithSkills($conn, $userId, $username, $bio, $skills = [], $avatar = null)
{
    // Update user basic info
    if ($avatar) {
        $stmt = $conn->prepare("UPDATE users SET user_name = ?, bio = ?, profile_pic = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("sssi", $username, $bio, $avatar, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET user_name = ?, bio = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("ssi", $username, $bio, $userId);
    }
    $stmt->execute();
    $stmt->close();

    // Delete old skills
    $stmt = $conn->prepare("DELETE FROM user_skills WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Insert new skills
    if (!empty($skills)) {
        $stmt = $conn->prepare("INSERT INTO user_skills (user_id, skill_id, role) VALUES (?, ?, ?)");
        $role = $_SESSION['user_role'] ?? 'learner';
        foreach ($skills as $skillId) {
            $stmt->bind_param("iis", $userId, $skillId, $role);
            $stmt->execute();
        }
        $stmt->close();
    }

    return true;
}

// Learner only completed skills
function getLearnerEnrolledSkills($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT s.skill_id, s.skill_name, e.status
        FROM enrollments e
        JOIN skills s ON e.skills_skill_id = s.skill_id
        WHERE e.users_user_id=?
        ORDER BY s.skill_name
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $res;
}



function isSkillCompleted($conn, $userId, $skillId)
{
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE users_user_id=? AND skills_skill_id=? AND status='completed'");
    $stmt->bind_param("ii", $userId, $skillId);
    $stmt->execute();
    $stmt->store_result();
    $completed = $stmt->num_rows > 0;
    $stmt->close();
    return $completed;
}


// ======================= ROLE-BASED DATA HELPERS =======================

function getEnrollmentsByRole($conn, $userId, $role)
{
    if ($role === 'teacher') {
        $query = "
            SELECT e.enrollment_id, u.user_name AS learner_name, s.skill_name, e.status, e.enrolled_at
            FROM enrollments e
            JOIN users u ON u.user_id = e.users_user_id
            JOIN skills s ON s.skill_id = e.skills_skill_id
            JOIN teachers t ON t.skills_skill_id = s.skill_id
            WHERE t.users_user_id = ?
            ORDER BY e.enrolled_at DESC
        ";
    } elseif ($role === 'learner') {
        $query = "
            SELECT e.enrollment_id, s.skill_name, e.status, e.enrolled_at
            FROM enrollments e
            JOIN skills s ON s.skill_id = e.skills_skill_id
            WHERE e.users_user_id = ?
            ORDER BY e.enrolled_at DESC
        ";
    } else { // admin
        $query = "
            SELECT e.enrollment_id, u.user_name AS learner_name, s.skill_name, e.status, e.enrolled_at
            FROM enrollments e
            JOIN users u ON u.user_id = e.users_user_id
            JOIN skills s ON s.skill_id = e.skills_skill_id
            ORDER BY e.enrolled_at DESC
        ";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    $stmt->close();
    return $data;
}

function getFeedbacksByRole($conn, $userId, $role)
{
    if ($role === 'teacher') {
        $query = "
            SELECT f.feedback_id, u.user_name AS learner_name, s.skill_name, f.comment, f.rating, f.created_at, f.feedback_given
            FROM feedback f
            JOIN users u ON f.users_user_id = u.user_id
            JOIN skills s ON s.skill_id = f.skills_skill_id
            JOIN teachers t ON t.skills_skill_id = s.skill_id
            WHERE t.users_user_id = ?
            ORDER BY f.created_at DESC
        ";
    } elseif ($role === 'learner') {
        $query = "
            SELECT f.feedback_id, s.skill_name, f.comment, f.rating, f.created_at, f.feedback_given
            FROM feedback f
            JOIN skills s ON s.skill_id = f.skills_skill_id
            WHERE f.users_user_id = ?
            ORDER BY f.created_at DESC
        ";
    } else { // admin
        $query = "
            SELECT f.feedback_id, u.user_name AS learner_name, s.skill_name, f.comment, f.rating, f.created_at, f.feedback_given
            FROM feedback f
            JOIN users u ON f.users_user_id = u.user_id
            JOIN skills s ON s.skill_id = f.skills_skill_id
            ORDER BY f.created_at DESC
        ";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    $stmt->close();
    return $data;
}

function getCertificatesByRole($conn, $userId, $role)
{
    if ($role === 'teacher') {
        $query = "
            SELECT c.id, u.user_name AS learner_name, s.skill_name, c.certificate_code, c.issued_date, c.status
            FROM certificates c
            JOIN learners l ON l.id = c.learner_id
            JOIN users u ON u.user_id = l.users_user_id
            JOIN skills s ON s.skill_id = c.course_id
            JOIN teachers t ON t.skills_skill_id = s.skill_id
            WHERE t.users_user_id = ?
            ORDER BY c.issued_date DESC
        ";
    } elseif ($role === 'learner') {
        $query = "
            SELECT c.id, s.skill_name, c.certificate_code, c.issued_date, c.status
            FROM certificates c
            JOIN skills s ON s.skill_id = c.course_id
            WHERE c.learner_id = ?
            ORDER BY c.issued_date DESC
        ";
    } else { // admin
        $query = "
            SELECT c.id, u.user_name AS learner_name, s.skill_name, c.certificate_code, c.issued_date, c.status
            FROM certificates c
            JOIN learners l ON l.id = c.learner_id
            JOIN users u ON u.user_id = l.users_user_id
            JOIN skills s ON s.skill_id = c.course_id
            ORDER BY c.issued_date DESC
        ";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    $stmt->close();
    return $data;
}


function getSkillsByRole($conn, $userId, $role)
{
    if ($role === 'teacher') {
        $query = "
            SELECT s.skill_id, s.skill_name, s.description
            FROM skills s
            JOIN teachers t ON t.skills_skill_id = s.skill_id
            WHERE t.users_user_id = ?
            ORDER BY s.skill_name ASC
        ";
    } elseif ($role === 'learner') {
        $query = "
            SELECT s.skill_id, s.skill_name, s.description
            FROM skills s
            JOIN enrollments e ON e.skills_skill_id = s.skill_id
            WHERE e.users_user_id = ?
            ORDER BY s.skill_name ASC
        ";
    } else { // admin
        $query = "
            SELECT s.skill_id, s.skill_name, s.description
            FROM skills s
            ORDER BY s.skill_name ASC
        ";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $skills = [];
    while ($row = $result->fetch_assoc()) $skills[] = $row;
    $stmt->close();
    return $skills;
}

// ======================= PROFILE PICTURE UPLOAD HELPER =======================

function saveProfilePicture($file, $userId)
{
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!isset($file['name']) || $file['name'] === '') {
        return [null, null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
        ];
        $msg = $errors[$file['error']] ?? 'Unknown upload error.';
        return [null, "Upload failed: $msg (Error code {$file['error']})"];
    }

    $fileExt = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowed)) {
        return [null, 'Only JPG, JPEG, PNG, GIF, or WEBP images are allowed.'];
    }

    $uploadDir = __DIR__ . '/../uploads/profile_pics/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return [null, 'Failed to create upload directory.'];
    }

    $newFileName = 'uploads/profile_pics/' . $userId . '_' . time() . '.' . $fileExt;
    $targetPath = __DIR__ . '/../' . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [$newFileName, null];
    } else {
        return [null, 'Failed to upload the profile picture. Check folder permissions: ' . realpath($uploadDir)];
    }
}

// ======================= SIMPLE PROFILE UPDATE =======================

function updateUserProfile($conn, $userId, $username, $bio, $avatar = null)
{
    if ($avatar) {
        $stmt = $conn->prepare("UPDATE users SET user_name = ?, bio = ?, profile_pic = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("sssi", $username, $bio, $avatar, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET user_name = ?, bio = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("ssi", $username, $bio, $userId);
    }
    $stmt->execute();
    $stmt->close();
    return true;
}



function saveSkillThumbnail($file)
{
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!isset($file['name']) || $file['name'] === '') return [null, 'No file provided'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
        ];
        $msg = $errors[$file['error']] ?? 'Unknown upload error.';
        return [null, $msg];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return [null, 'Invalid file type'];

    $uploadDir = __DIR__ . '/../uploads/skills/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return [null, 'Failed to create upload directory'];
    }

    $newFileName = 'uploads/skills/' . uniqid('skill_') . '.' . $ext;
    $targetPath = __DIR__ . '/../' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [null, 'Failed to move uploaded file'];
    }

    return [$newFileName, null];
}
