<?php
// ======================= SESSION & ROLE CHECK =======================
function checkRole($requiredRole)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'], $_SESSION['user_role'])) {
        header("Location: /index.php");
        exit();
    }

    if ($_SESSION['user_role'] !== $requiredRole) {
        switch ($_SESSION['user_role']) {
            case 'admin':
                header("Location: ../dashboard/admin_dashboard.php");
                break;
            case 'teacher':
                header("Location: ../dashboard/teacher_dashboard.php");
                break;
            case 'learner':
                header("Location: ../dashboard/learner_dashboard.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
    }
}

function logActivity($conn, $user_id, $username, $action)
{
    $stmt = $conn->prepare("INSERT INTO activities (user_id, username, action) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $username, $action);
    $stmt->execute();
    $stmt->close();
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
    if ($avatar) {
        $stmt = $conn->prepare("UPDATE users SET user_name = ?, bio = ?, profile_pic = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $username, $bio, $avatar, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET user_name = ?, bio = ? WHERE user_id = ?");
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
        $role = $_SESSION['user_role'];
        foreach ($skills as $skillId) {
            $stmt->bind_param("iis", $userId, $skillId, $role);
            $stmt->execute();
        }
        $stmt->close();
    }

    return true;
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
            JOIN users u ON u.user_id = f.users_user_id
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
            JOIN users u ON u.user_id = f.users_user_id
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
            SELECT c.id, l.user_name AS learner_name, s.skill_name, c.certificate_code, c.issued_date, c.status
            FROM certificates c
            JOIN learners l ON l.id = c.learner_id
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
            SELECT c.id, l.user_name AS learner_name, s.skill_name, c.certificate_code, c.issued_date, c.status
            FROM certificates c
            JOIN learners l ON l.id = c.learner_id
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

/**
 * Helper: Save uploaded profile picture with full error handling
 */
function saveProfilePicture($file, $userId)
{
    // Allowed extensions
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    // Check if file exists
    if (!isset($file['name']) || $file['name'] === '') {
        return [null, null]; // No file uploaded
    }

    // Check PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
        ];
        $code = $file['error'];
        $message = $errorMessages[$code] ?? 'Unknown upload error.';
        return [null, "Upload failed: $message (Error code $code)"];
    }

    // Check extension
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowed)) {
        return [null, 'Only JPG, JPEG, PNG, GIF, or WEBP images are allowed.'];
    }

    // Prepare upload directory
    $uploadDir = __DIR__ . '/../uploads/profile_pics/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return [null, 'Failed to create upload directory.'];
        }
    }

    // Final path
    $newFileName = 'uploads/profile_pics/' . $userId . '_' . time() . '.' . $fileExt;
    $targetPath = __DIR__ . '/../' . $newFileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [$newFileName, null];
    } else {
        return [null, 'Failed to upload the profile picture. Check folder permissions: ' . realpath($uploadDir)];
    }
}
