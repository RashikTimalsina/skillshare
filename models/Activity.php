<?php
class Activity
{
    public $id;
    public $user_id;
    public $username;
    public $action;
    public $created_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->username = $data['username'] ?? '';
        $this->action = $data['action'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    // Log a new activity
    public static function log($conn, $user_id, $username, $action)
    {
        $stmt = $conn->prepare("INSERT INTO activities (user_id, username, action, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $user_id, $username, $action);
        return $stmt->execute();
    }

    // Get recent activities for a user
    public static function getRecentByUserId($conn, $user_id, $limit = 10)
    {
        $stmt = $conn->prepare("SELECT * FROM activities WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $activities = [];
        while ($row = $res->fetch_assoc()) {
            $activities[] = new Activity($row);
        }
        return $activities;
    }

    public static function getRecentBySkillId($conn, $skill_id, $limit = 10)
    {
        $query = "
            SELECT a.*
            FROM activities a
            JOIN user_skills us ON us.user_id = a.user_id
            WHERE us.skill_id=?
            ORDER BY a.created_at DESC
            LIMIT ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $skill_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $activities = [];
        while ($row = $res->fetch_assoc()) {
            $activities[] = new Activity($row);
        }
        $stmt->close();
        return $activities;
    }


    // Get all activities (optional)
    public static function getAll($conn)
    {
        $res = $conn->query("SELECT * FROM activities ORDER BY created_at DESC");
        $activities = [];
        while ($row = $res->fetch_assoc()) {
            $activities[] = new Activity($row);
        }
        return $activities;
    }
}
