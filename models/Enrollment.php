<?php
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

    public static function getAllByUserId($conn, $user_id)
    {
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE users_user_id=? ORDER BY enrolled_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $enrollments = [];
        while ($row = $res->fetch_assoc()) {
            $enrollments[] = new Enrollment($row);
        }
        $stmt->close();
        return $enrollments;
    }

    public static function getAllBySkillId($conn, $skill_id)
    {
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE skills_skill_id=? ORDER BY enrolled_at DESC");
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $enrollments = [];
        while ($row = $res->fetch_assoc()) {
            $enrollments[] = new Enrollment($row);
        }
        $stmt->close();
        return $enrollments;
    }

    
    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE enrollment_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Enrollment($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM enrollments ORDER BY enrolled_at DESC");
        $enrollments = [];
        while ($row = $result->fetch_assoc()) {
            $enrollments[] = new Enrollment($row);
        }
        return $enrollments;
    }

    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO enrollments (users_user_id, skills_skill_id, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $data['users_user_id'], $data['skills_skill_id'], $data['status']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE enrollments SET users_user_id=?, skills_skill_id=?, status=? WHERE enrollment_id=?");
        $stmt->bind_param("iisi", $data['users_user_id'], $data['skills_skill_id'], $data['status'], $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE enrollment_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
