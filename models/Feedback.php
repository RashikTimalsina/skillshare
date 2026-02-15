<?php
class Feedback
{
    public $feedback_id;
    public $users_user_id;
    public $skills_skill_id;
    public $rating;
    public $comment;
    public $feedback_given;
    public $created_at;
    public $user_name;
    public $skill_name;


    public function __construct($data = [])
    {
        $this->feedback_id = $data['feedback_id'] ?? null;
        $this->users_user_id = $data['users_user_id'] ?? null;
        $this->skills_skill_id = $data['skills_skill_id'] ?? null;
        $this->rating = $data['rating'] ?? 0;
        $this->comment = $data['comment'] ?? '';
        $this->feedback_given = $data['feedback_given'] ?? 'no';
        $this->created_at = $data['created_at'] ?? null;
        $this->user_name = $data['user_name'] ?? '';
        $this->skill_name = $data['skill_name'] ?? '';
    }

    // Get feedback by ID
    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM feedback WHERE feedback_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ? new Feedback($res) : null;
    }

    // Check if feedback exists for a user & skill
    public static function existsForUserAndSkill($conn, $userId, $skillId)
    {
        $stmt = $conn->prepare("SELECT feedback_id FROM feedback WHERE users_user_id=? AND skills_skill_id=? LIMIT 1");
        $stmt->bind_param("ii", $userId, $skillId);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    // Create feedback
    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO feedback (users_user_id, skills_skill_id, rating, comment, feedback_given, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
            "iiiss",
            $data['users_user_id'],
            $data['skills_skill_id'],
            $data['rating'],
            $data['comment'],
            $data['feedback_given']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id ?: false;
    }

    // Update feedback
    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE feedback SET rating=?, comment=?, feedback_given=? WHERE feedback_id=?");
        $stmt->bind_param("issi", $data['rating'], $data['comment'], $data['feedback_given'], $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Delete feedback
    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Fetch all feedbacks (admin)
    public static function getAll($conn, $search = '', $sort = 'ASC')
    {
        $sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT f.*, u.user_name, s.skill_name
                FROM feedback f
                JOIN users u ON f.users_user_id = u.user_id
                JOIN skills s ON f.skills_skill_id = s.skill_id
                WHERE u.user_name LIKE ? OR s.skill_name LIKE ? OR CAST(f.feedback_id AS CHAR) LIKE ?
                ORDER BY f.feedback_id $sort";
        $stmt = $conn->prepare($sql);
        $p = "%$search%";
        $stmt->bind_param("sss", $p, $p, $p);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = new Feedback($row);
        $stmt->close();
        return $rows;
    }

    // Fetch feedbacks for a specific user (learner)
    public static function getByUser($conn, $userId)
    {
        $stmt = $conn->prepare("
        SELECT f.*, s.skill_name, u.user_name
        FROM feedback f
        JOIN skills s ON f.skills_skill_id = s.skill_id
        JOIN users u ON f.users_user_id = u.user_id
        WHERE f.users_user_id=?
        ORDER BY f.created_at DESC
    ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = new Feedback($row);
        $stmt->close();
        return $rows;
    }


    // Fetch feedbacks for teacher's skills
    public static function getByTeacher($conn, $teacherId)
    {
        $sql = "SELECT f.*, u.user_name, s.skill_name
                FROM feedback f
                JOIN skills s ON f.skills_skill_id = s.skill_id
                JOIN users u ON f.users_user_id = u.user_id
                JOIN teachers t ON t.skills_skill_id = s.skill_id
                WHERE t.users_user_id = ?
                ORDER BY f.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = new Feedback($row);
        $stmt->close();
        return $rows;
    }

    public static function getByUserId($conn, $userId)
    {
        $sql = "
            SELECT *
            FROM feedback
            WHERE users_user_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =============================
    // Fetch feedbacks by multiple skill IDs
    // =============================
    public static function getBySkillIds($conn, $skill_ids = [])
    {
        $feedbacks = [];
        if (empty($skill_ids)) return $feedbacks;

        $placeholders = implode(',', array_fill(0, count($skill_ids), '?'));
        $types = str_repeat('i', count($skill_ids));

        $sql = "SELECT * FROM feedback WHERE skills_skill_id IN ($placeholders) ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param($types, ...$skill_ids);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $feedbacks[] = new Feedback($row);
        }

        $stmt->close();
        return $feedbacks;
    }

}
