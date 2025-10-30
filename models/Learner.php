<?php
class Learner
{
    public $id;
    public $learner_id;
    public $users_user_id;
    public $skills_skill_id;
    public $status;
    public $bio;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->learner_id = $data['learner_id'] ?? null;
        $this->users_user_id = $data['users_user_id'] ?? null;
        $this->skills_skill_id = $data['skills_skill_id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM learners WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Learner($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM learners ORDER BY created_at DESC");
        $learners = [];
        while ($row = $result->fetch_assoc()) {
            $learners[] = new Learner($row);
        }
        return $learners;
    }

    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO learners (learner_id, users_user_id, skills_skill_id, status, bio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $data['learner_id'], $data['users_user_id'], $data['skills_skill_id'], $data['status'], $data['bio']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE learners SET learner_id=?, users_user_id=?, skills_skill_id=?, status=?, bio=? WHERE id=?");
        $stmt->bind_param("siissi", $data['learner_id'], $data['users_user_id'], $data['skills_skill_id'], $data['status'], $data['bio'], $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM learners WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
