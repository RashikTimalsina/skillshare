<?php
require_once __DIR__ . '/Certificate.php';

class Enrollment
{
    public $enrollment_id;
    public $users_user_id;
    public $skills_skill_id;
    public $status;
    public $enrolled_at;

    public function __construct($data = [])
    {
        $this->enrollment_id = $data['enrollment_id'] ?? null;
        $this->users_user_id = $data['users_user_id'] ?? null;
        $this->skills_skill_id = $data['skills_skill_id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->enrolled_at = $data['enrolled_at'] ?? null;
    }

    // Create enrollment
    public static function create($conn, $data)
    {
        // Prevent duplicate enrollment
        $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE users_user_id=? AND skills_skill_id=?");
        $stmt->bind_param("ii", $data['users_user_id'], $data['skills_skill_id']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return false; // Already enrolled
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO enrollments (users_user_id, skills_skill_id, status, enrolled_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $data['users_user_id'], $data['skills_skill_id'], $data['status']);
        $success = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $success ? $id : false;
    }

    // Update status
    public static function updateStatus($conn, $enrollmentId, $status, $issuedBy = null)
    {
        // Fetch current status
        $stmt = $conn->prepare("SELECT status, users_user_id FROM enrollments WHERE enrollment_id=? LIMIT 1");
        $stmt->bind_param("i", $enrollmentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return false;

        $currentStatus = $row['status'];
        $learnerId = $row['users_user_id'];

        // Only allow update if current status is 'enrolled'
        if ($currentStatus !== 'enrolled') return false;

        // Update status
        $stmt = $conn->prepare("UPDATE enrollments SET status=? WHERE enrollment_id=?");
        $stmt->bind_param("si", $status, $enrollmentId);
        $success = $stmt->execute();
        $stmt->close();

        // Auto-issue certificate if completed
        if ($success && $status === 'completed') {
            Certificate::createForEnrollment($conn, $enrollmentId, $learnerId, $issuedBy ?? 0);
        }

        return $success;
    }


    // Get enrollments by learner
    public static function getByUserId($conn, $userId)
    {
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE users_user_id=? ORDER BY enrolled_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $enrollments = [];
        while ($row = $res->fetch_assoc()) {
            $enrollments[] = new Enrollment($row);
        }
        $stmt->close();
        return $enrollments;
    }

    public static function getByLearner($conn, $learnerId)
    {
        $sql = "
        SELECT 
            e.enrollment_id,
            e.skills_skill_id,   -- <-- add this
            e.status,
            e.enrolled_at,
            s.skill_name,
            c.category_name
        FROM enrollments e
        JOIN skills s ON e.skills_skill_id = s.skill_id
        LEFT JOIN categories c ON s.categories_category_id = c.category_id
        WHERE e.users_user_id = ?
        ORDER BY e.enrolled_at DESC
    ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $learnerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }



    // Get enrollments for a teacher (all students enrolled in teacher's skills)
    public static function getByTeacherSkills($conn, $teacherUserId)
    {
        $stmt = $conn->prepare("
            SELECT e.* 
            FROM enrollments e
            JOIN skills s ON e.skills_skill_id = s.skill_id
            JOIN teachers t ON s.skill_id = t.skills_skill_id
            WHERE t.users_user_id=?
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->bind_param("i", $teacherUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        $enrollments = [];
        while ($row = $res->fetch_assoc()) {
            $enrollments[] = new Enrollment($row);
        }
        $stmt->close();
        return $enrollments;
    }

    public static function getAllByUserId($conn, $userId)
    {
        $sql = "
            SELECT 
                e.*, 
                s.skill_name
            FROM enrollments e
            JOIN skills s ON e.skills_skill_id = s.skill_id
            WHERE e.users_user_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function getById($conn, $enrollmentId)
    {
        $stmt = $conn->prepare("
        SELECT * 
        FROM enrollments 
        WHERE enrollment_id = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $enrollmentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null; // returns associative array or null if not found
    }


    public static function calculateProgress($conn, $enrollmentId)
    {
        $sql = "
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN completed='yes' THEN 1 ELSE 0 END) as done
        FROM learner_module_progress
        WHERE enrollment_id = ?
    ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $enrollmentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return ($result['total'] > 0) ? round(($result['done'] / $result['total']) * 100) : 0;
    }
}
