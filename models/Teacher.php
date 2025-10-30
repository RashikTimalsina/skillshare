<?php
class Teacher
{
    public $id;
    public $teacher_id;
    public $teacher_skill_id;
    public $users_user_id;
    public $skills_skill_id;
    public $status;
    public $rating;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->teacher_id = $data['teacher_id'] ?? null;
        $this->teacher_skill_id = $data['teacher_skill_id'] ?? null;
        $this->users_user_id = $data['users_user_id'] ?? null;
        $this->skills_skill_id = $data['skills_skill_id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->rating = $data['rating'] ?? null;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Teacher($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM teachers ORDER BY id DESC");
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = new Teacher($row);
        }
        return $teachers;
    }

    public static function getAllByUserId($conn, $user_id)
    {
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE users_user_id=? ORDER BY id DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $teachers = [];
        while ($row = $res->fetch_assoc()) {
            $teachers[] = new Teacher($row);
        }
        $stmt->close();
        return $teachers;
    }

    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO teachers (teacher_id, teacher_skill_id, users_user_id, skills_skill_id, status, rating) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiisi", $data['teacher_id'], $data['teacher_skill_id'], $data['users_user_id'], $data['skills_skill_id'], $data['status'], $data['rating']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE teachers SET teacher_id=?, teacher_skill_id=?, users_user_id=?, skills_skill_id=?, status=?, rating=? WHERE id=?");
        $stmt->bind_param("siiisii", $data['teacher_id'], $data['teacher_skill_id'], $data['users_user_id'], $data['skills_skill_id'], $data['status'], $data['rating'], $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
