
<?php
class Feedback
{
    public $feedback_id;
    public $users_user_id;
    public $skills_skill_id;
    public $rating;
    public $comment;
    public $created_at;
    public $feedback_given;

    public function __construct($data = [])
    {
        $this->feedback_id = $data['feedback_id'] ?? null;
        $this->users_user_id = $data['users_user_id'] ?? null;
        $this->skills_skill_id = $data['skills_skill_id'] ?? null;
        $this->rating = $data['rating'] ?? null;
        $this->comment = $data['comment'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->feedback_given = $data['feedback_given'] ?? null;
    }

    public static function getByUserId($conn, $user_id)
    {
        $stmt = $conn->prepare("SELECT * FROM feedback WHERE users_user_id=? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $feedbacks = [];
        while ($row = $res->fetch_assoc()) {
            $feedbacks[] = new Feedback($row);
        }
        $stmt->close();
        return $feedbacks;
    }

    public static function getBySkillId($conn, $skill_id)
    {
        $stmt = $conn->prepare("SELECT * FROM feedback WHERE skills_skill_id=? ORDER BY created_at DESC");
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $feedbacks = [];
        while ($row = $res->fetch_assoc()) {
            $feedbacks[] = new Feedback($row);
        }
        $stmt->close();
        return $feedbacks;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM feedback WHERE feedback_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Feedback($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC");
        $feedbacks = [];
        while ($row = $result->fetch_assoc()) {
            $feedbacks[] = new Feedback($row);
        }
        return $feedbacks;
    }

    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO feedback (users_user_id, skills_skill_id, rating, comment, feedback_given) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $data['users_user_id'], $data['skills_skill_id'], $data['rating'], $data['comment'], $data['feedback_given']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE feedback SET users_user_id=?, skills_skill_id=?, rating=?, comment=?, feedback_given=? WHERE feedback_id=?");
        $stmt->bind_param("iissi", $data['users_user_id'], $data['skills_skill_id'], $data['rating'], $data['comment'], $data['feedback_given'], $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
