<?php
class User
{
    public $user_id;
    public $user_name;
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
        $this->user_name = $data['user_name'] ?? null;
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
        $result = $conn->query("SELECT * FROM users ORDER BY user_name ASC");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = new User($row);
        }
        return $users;
    }

    public static function getAllByRole($conn, $role)
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role=? ORDER BY user_name ASC");
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

    // --- NEW CRUD METHODS ---
    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO users (user_name, email, role, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param(
            "ssss",
            $data['user_name'],
            $data['email'],
            $data['role'],
            $data['password']
        );
        $stmt->execute();
        $stmt->close();
        return $conn->insert_id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE users SET user_name=?, email=?, role=? WHERE user_id=?");
        $stmt->bind_param(
            "sssi",
            $data['user_name'],
            $data['email'],
            $data['role'],
            $id
        );
        $stmt->execute();
        $stmt->close();
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}



