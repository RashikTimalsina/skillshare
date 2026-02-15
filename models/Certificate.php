<?php
class Certificate
{
    public $id;
    public $learner_id;
    public $course_id;
    public $certificate_code;
    public $issued_date;
    public $expiry_date;
    public $status;
    public $issued_by;
    public $remarks;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->learner_id = $data['learner_id'] ?? null;
        $this->course_id = $data['course_id'] ?? null;
        $this->certificate_code = $data['certificate_code'] ?? null;
        $this->issued_date = $data['issued_date'] ?? null;
        $this->expiry_date = $data['expiry_date'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->issued_by = $data['issued_by'] ?? null;
        $this->remarks = $data['remarks'] ?? null;
    }

    // -------------------------
    // Fetch single certificate by ID
    // -------------------------
    public static function getById(mysqli $conn, int $id)
    {
        $stmt = $conn->prepare("
        SELECT c.*, s.skill_name, u1.user_name AS learner_name, u2.user_name AS issuer_name
        FROM certificates c
        JOIN skills s ON c.course_id = s.skill_id
        JOIN users u1 ON c.learner_id = u1.user_id
        JOIN users u2 ON c.issued_by = u2.user_id
        WHERE c.id = ? AND c.status='active'
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: []; // return array instead of object
    }

    // -------------------------
    // Fetch all certificates
    // -------------------------
    public static function getAll(mysqli $conn)
    {
        $sql = "
            SELECT c.*, s.skill_name, u1.user_name AS learner_name, u2.user_name AS issuer_name
            FROM certificates c
            JOIN skills s ON c.course_id = s.skill_id
            JOIN users u1 ON c.learner_id = u1.user_id
            JOIN users u2 ON c.issued_by = u2.user_id
            ORDER BY c.issued_date DESC
        ";
        $res = $conn->query($sql);
        $certificates = [];
        while ($row = $res->fetch_assoc()) {
            $certificates[] = $row;
        }
        return $certificates;
    }


    public static function getAllByLearner(mysqli $conn, int $learnerId)
    {
        $stmt = $conn->prepare("
        SELECT c.*, s.skill_name, u1.user_name AS learner_name, u2.user_name AS issuer_name
        FROM certificates c
        JOIN skills s ON c.course_id = s.skill_id
        JOIN users u1 ON c.learner_id = u1.user_id
        JOIN users u2 ON c.issued_by = u2.user_id
        WHERE c.learner_id = ? AND c.status='active'
        ORDER BY c.issued_date DESC
    ");
        $stmt->bind_param("i", $learnerId);
        $stmt->execute();
        $res = $stmt->get_result();
        $certificates = [];
        while ($row = $res->fetch_assoc()) {
            $certificates[] = $row; // keep as array
        }
        $stmt->close();
        return $certificates;
    }

    // -------------------------
    // Create / insert certificate
    // -------------------------
    public static function create(mysqli $conn, array $data)
    {
        $stmt = $conn->prepare("
            INSERT INTO certificates 
                (learner_id, course_id, certificate_code, issued_date, expiry_date, status, issued_by, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissssis",
            $data['learner_id'],
            $data['course_id'],
            $data['certificate_code'],
            $data['issued_date'],
            $data['expiry_date'],
            $data['status'],
            $data['issued_by'],
            $data['remarks']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    // -------------------------
    // Issue certificate (teacher/admin)
    // -------------------------
    public static function issue(mysqli $conn, array $data)
    {
        session_start();
        $issuerRole = $_SESSION['user_role'] ?? 'learner';
        $issuerId = $_SESSION['user_id'] ?? 0;

        // Teacher can issue only their skill
        if ($issuerRole === 'teacher') {
            $stmt = $conn->prepare("
                SELECT 1 FROM teachers 
                WHERE users_user_id=? AND skills_skill_id=? AND status='active' LIMIT 1
            ");
            $stmt->bind_param("ii", $issuerId, $data['course_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $stmt->close();
                return false;
            }
            $stmt->close();
            $data['issued_by'] = $issuerId;
        } elseif ($issuerRole === 'admin') {
            // Admin must preserve teacher as issuer
            $stmt = $conn->prepare("
                SELECT users_user_id FROM teachers 
                WHERE skills_skill_id=? AND status='active' LIMIT 1
            ");
            $stmt->bind_param("i", $data['course_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            $teacher = $res->fetch_assoc();
            $stmt->close();
            if (!$teacher) return false;
            $data['issued_by'] = $teacher['users_user_id'];
        } else {
            return false;
        }

        // Prevent duplicate
        $stmt = $conn->prepare("
            SELECT id FROM certificates WHERE learner_id=? AND course_id=? AND status='active'
        ");
        $stmt->bind_param("ii", $data['learner_id'], $data['course_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $stmt->close();
            return false;
        }
        $stmt->close();

        // Defaults
        $data['issued_date'] = date('Y-m-d');
        $data['expiry_date'] = $data['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));
        $data['status'] = $data['status'] ?? 'active';
        $data['remarks'] = $data['remarks'] ?? '';

        return self::create($conn, $data);
    }

    // -------------------------
    // Revoke certificate
    // -------------------------
    public static function revoke(mysqli $conn, int $id)
    {
        session_start();
        $issuerRole = $_SESSION['user_role'] ?? 'learner';
        $issuerId = $_SESSION['user_id'] ?? 0;

        if ($issuerRole === 'teacher') {
            $stmt = $conn->prepare("
                UPDATE certificates c
                JOIN skills s ON c.course_id = s.skill_id
                JOIN teachers t ON s.skill_id = t.skills_skill_id
                SET c.status='revoked'
                WHERE c.id=? AND c.status='active' AND t.users_user_id=?
            ");
            $stmt->bind_param("ii", $id, $issuerId);
        } else {
            $stmt = $conn->prepare("
                UPDATE certificates SET status='revoked' 
                WHERE id=? AND status='active'
            ");
            $stmt->bind_param("i", $id);
        }

        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    // -------------------------
    // Auto-issue after enrollment completion
    // -------------------------
    public static function createForEnrollment(mysqli $conn, int $enrollmentId, int $learnerId, $issuedBy = null)
    {
        $enrollment = Enrollment::getById($conn, $enrollmentId);
        if (!$enrollment) return null;

        $skillId = $enrollment['skills_skill_id'];

        // Prevent duplicates
        $stmt = $conn->prepare("SELECT id FROM certificates WHERE learner_id=? AND course_id=? AND status='active'");
        $stmt->bind_param("ii", $learnerId, $skillId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $stmt->close();
            return null;
        }
        $stmt->close();

        // Determine issuer
        if (!$issuedBy) {
            $stmt = $conn->prepare("
                SELECT u.user_id FROM teachers t
                JOIN users u ON t.users_user_id = u.user_id
                WHERE t.skills_skill_id=? AND t.status='active'
                LIMIT 1
            ");
            $stmt->bind_param("i", $skillId);
            $stmt->execute();
            $res = $stmt->get_result();
            $teacher = $res->fetch_assoc();
            $stmt->close();

            if ($teacher) {
                $issuedBy = $teacher['user_id'];
            } else {
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE role='admin' LIMIT 1");
                $stmt->execute();
                $res = $stmt->get_result();
                $admin = $res->fetch_assoc();
                $stmt->close();
                $issuedBy = $admin['user_id'] ?? null;
                if (!$issuedBy) return null;
            }
        }

        $data = [
            'learner_id' => $learnerId,
            'course_id' => $skillId,
            'certificate_code' => "CERT-" . time() . "-" . $learnerId,
            'issued_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'active',
            'issued_by' => $issuedBy,
            'remarks' => 'Auto-issued'
        ];

        return self::create($conn, $data);
    }
}
