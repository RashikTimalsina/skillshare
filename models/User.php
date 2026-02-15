<?php

class User
{
    public $user_id;
    public $user_name;
    public $email;
    public $password;
    public $role;
    public $status;
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
        $this->status = $data['status'] ?? 'active';
        $this->bio = $data['bio'] ?? null;
        $this->profile_pic = $data['profile_pic'] ?? null;
        $this->agreed_terms = $data['agreed_terms'] ?? 0;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /* =======================
       READ OPERATIONS
    ======================= */

    // Admin: view all users
    public static function getAll($conn)
    {
        $stmt = $conn->prepare(
            "SELECT user_id, user_name, email, role, status 
             FROM users 
             ORDER BY user_name ASC"
        );
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
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? new User($row) : null;
    }

    public static function getByEmail($conn, $email)
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? new User($row) : null;
    }

    /* =======================
       ADMIN CONTROL OPERATIONS
    ======================= */

    // Activate / Deactivate user
    public static function updateStatus($conn, $id, $status)
    {
        $stmt = $conn->prepare(
            "UPDATE users SET status=?, updated_at=NOW() WHERE user_id=?"
        );
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    // Change role (learner / teacher)
    public static function updateRole($conn, $id, $role)
    {
        $stmt = $conn->prepare(
            "UPDATE users SET role=?, updated_at=NOW() WHERE user_id=?"
        );
        $stmt->bind_param("si", $role, $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /* =======================
       AUTH / SIGNUP OPERATIONS
       (NOT FOR ADMIN UI)
    ======================= */

    public static function create($conn, $data)
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users 
            (user_name, email, password, role, status, agreed_terms, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'active', 1, NOW(), NOW())"
        );

        $stmt->bind_param(
            "ssss",
            $data['user_name'],
            $data['email'],
            $hashedPassword,
            $data['role']
        );

        $success = $stmt->execute();
        $insertId = $conn->insert_id;
        $stmt->close();

        return $success ? $insertId : false;
    }


    /* =======================
   DELETE USER (ADMIN ONLY)
======================= */
    public static function delete($conn, $id)
    {
        // Prevent deleting admin users (optional, safety)
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $role = $stmt->get_result()->fetch_assoc()['role'] ?? null;
        $stmt->close();

        if ($role === 'admin') {
            return false; // Cannot delete admin
        }

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
    public static function getAllByRole($conn, string $role): array
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY user_id ASC");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_object()) {
            $users[] = $row;
        }
        return $users;
    }

    // In User.php

    // Get learner's enrolled skills
    public static function getLearnerSkills($conn, $userId): array
    {
        $stmt = $conn->prepare("
        SELECT s.skill_name
        FROM enrollments e
        JOIN skills s ON e.skills_skill_id = s.skill_id
        WHERE e.users_user_id=? AND e.status IN ('enrolled','completed')
    ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_column($res, 'skill_name');
    }

    // Get teacher's active skills
    public static function getTeacherSkills($conn, $userId): array
    {
        $stmt = $conn->prepare("
        SELECT s.skill_name
        FROM teachers t
        JOIN skills s ON t.skills_skill_id = s.skill_id
        WHERE t.users_user_id=? AND t.status='active'
    ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_column($res, 'skill_name');
    }
}
