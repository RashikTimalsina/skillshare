<?php
class User
{
    public $user_id;
    public $full_name;
    public $email;
    public $password;
    public $role;
    public $bio;
    public $profile_pic;
    public $agreed_terms;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        $this->user_id = $data['user_id'] ?? null;
        $this->full_name = $data['full_name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->profile_pic = $data['profile_pic'] ?? null;
        $this->agreed_terms = $data['agreed_terms'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM users ORDER BY full_name ASC");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = new User($row);
        }
        return $users;
    }

    public static function getAllByRole($conn, $role)
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role=? ORDER BY full_name ASC");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = new User($row);
        }
        $stmt->close();
        return $users;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new User($result) : null;
    }
}
