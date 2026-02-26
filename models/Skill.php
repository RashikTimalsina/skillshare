<?php
class Skill
{
    public $skill_id;
    public $skill_name;
    public $description;
    public $categories_category_id;
    public $category_name;
    public $offered_by;
    public $offered_by_name;
    public $thumbnail;
    public $created_at;
    public $updated_at;
    public $owned_by_user = false;

    public function __construct($data = [])
    {
        $this->skill_id = $data['skill_id'] ?? null;
        $this->skill_name = $data['skill_name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->categories_category_id = $data['categories_category_id'] ?? null;
        $this->category_name = $data['category_name'] ?? null;
        $this->offered_by = $data['created_by'] ?? null;
        $this->offered_by_name = $data['offered_by_name'] ?? null;
        $this->thumbnail = $data['thumbnail'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->owned_by_user = $data['owned_by_user'] ?? false;
    }

    // --- CREATE SKILL ---
    public static function create($conn, $data, int $teacherId, array $file)
    {
        if (!isset($file['thumbnail']) || $file['thumbnail']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Thumbnail image is required. Error code: " . ($file['thumbnail']['error'] ?? 'N/A'));
        }

        $conn->begin_transaction();

        try {
            // 1️⃣ Check for duplicate skill name for this teacher
            $stmt = $conn->prepare("SELECT skill_id FROM skills WHERE skill_name = ? AND created_by = ?");
            $stmt->bind_param("si", $data['skill_name'], $teacherId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->close();
                $conn->rollback();
                return false; // duplicate skill for this teacher
            }
            $stmt->close();

            // 2️⃣ Save thumbnail
            list($thumbnailPath, $err) = saveSkillThumbnail($file['thumbnail']);
            if ($err) throw new Exception($err);

            $categoryId = !empty($data['categories_category_id']) ? (int)$data['categories_category_id'] : null;

            // 3️⃣ Insert skill
            $stmt = $conn->prepare("
            INSERT INTO skills (skill_name, description, categories_category_id, created_by, thumbnail, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
            $stmt->bind_param(
                "ssiss",
                $data['skill_name'],
                $data['description'],
                $categoryId,
                $teacherId,
                $thumbnailPath
            );

            if (!$stmt->execute()) {
                throw new Exception("Skill insert failed: " . $stmt->error);
            }

            $skillId = $stmt->insert_id;
            $stmt->close();

            // 4️⃣ Link teacher → skill (if not already exists)
            // 4️⃣ Link teacher → skill (ensure active status)
            $stmt = $conn->prepare("
             INSERT INTO teachers (users_user_id, skills_skill_id, status)
             VALUES (?, ?, 'active')
            ON DUPLICATE KEY UPDATE status='active'
            ");
            $stmt->bind_param("ii", $teacherId, $skillId);
            $stmt->execute();
            $stmt->close();

            // 5️⃣ Record in user_skills
            $stmt = $conn->prepare("INSERT INTO user_skills (user_id, skill_id, role) VALUES (?, ?, 'teacher')");
            $stmt->bind_param("ii", $teacherId, $skillId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into user_skills: " . $stmt->error);
            }
            $stmt->close();

            $conn->commit();
            return $skillId;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    // --- UPDATE SKILL ---
    public static function update($conn, $id, $data, $file = null)
    {
        $stmt = $conn->prepare("SELECT skill_id FROM skills WHERE skill_name=? AND skill_id<>?");
        $stmt->bind_param("si", $data['skill_name'], $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return false;
        }
        $stmt->close();

        $categoryId = !empty($data['categories_category_id']) ? (int)$data['categories_category_id'] : null;

        $sql = "UPDATE skills SET skill_name=?, description=?, categories_category_id=?, updated_at=NOW()";
        $params = [$data['skill_name'], $data['description'], $categoryId];
        $types = "ssi";

        // Handle optional thumbnail update
        if ($file && isset($file['thumbnail']) && $file['thumbnail']['error'] === UPLOAD_ERR_OK) {
            list($thumbnailPath, $err) = saveSkillThumbnail($file['thumbnail']);
            if ($err) throw new Exception($err);
            $sql .= ", thumbnail=?";
            $params[] = $thumbnailPath;
            $types .= "s";
        }



        $sql .= " WHERE skill_id=?";
        $params[] = $id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // --- DELETE SKILL ---
    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // --- SEARCH  SKILLS ---
    public static function getAllWithCategory($conn, $role, $userId, $search = '', $sort = 'ASC')
    {
        $sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';
        $search = trim($search);

        $hasSearch = $search !== '';
        $searchParam = $search . '%'; // prefix search only

        if ($role === 'learner') {

            $sql = "SELECT s.skill_id, s.skill_name, s.description, s.thumbnail,
                       s.created_by, u.user_name AS offered_by_name, c.category_name
                FROM skills s
                LEFT JOIN users u ON s.created_by = u.user_id
                LEFT JOIN categories c ON s.categories_category_id = c.category_id";

            if ($hasSearch) {
                $sql .= " WHERE LOWER(s.skill_name) LIKE LOWER(?)";
            }

            $sql .= " ORDER BY s.skill_id $sort";

            $stmt = $conn->prepare($sql);

            if ($hasSearch) {
                $stmt->bind_param("s", $searchParam);
            }
        } elseif ($role === 'teacher') {

            $sql = "SELECT s.skill_id, s.skill_name, s.description, s.created_by,
                       u.user_name AS offered_by_name, c.category_name,
                       (us.user_id IS NOT NULL) AS owned_by_user,
                       s.thumbnail, s.categories_category_id
                FROM skills s
                LEFT JOIN users u ON s.created_by = u.user_id
                LEFT JOIN categories c ON s.categories_category_id = c.category_id
                LEFT JOIN user_skills us
                       ON s.skill_id = us.skill_id AND us.user_id = ?";

            if ($hasSearch) {
                $sql .= " WHERE LOWER(s.skill_name) LIKE LOWER(?)";
            }

            $sql .= " ORDER BY s.skill_id $sort";

            $stmt = $conn->prepare($sql);

            if ($hasSearch) {
                $stmt->bind_param("is", $userId, $searchParam);
            } else {
                $stmt->bind_param("i", $userId);
            }
        } else { // admin

            $sql = "SELECT s.skill_id, s.skill_name, s.description, s.created_by,
                       u.user_name AS offered_by_name, c.category_name, s.thumbnail
                FROM skills s
                LEFT JOIN users u ON s.created_by = u.user_id
                LEFT JOIN categories c ON s.categories_category_id = c.category_id";

            if ($hasSearch) {
                $sql .= " WHERE LOWER(s.skill_name) LIKE LOWER(?)";
            }

            $sql .= " ORDER BY s.skill_id $sort";

            $stmt = $conn->prepare($sql);

            if ($hasSearch) {
                $stmt->bind_param("s", $searchParam);
            }
        }

        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $skills = [];
        while ($row = $result->fetch_assoc()) {
            $skills[] = new Skill($row);
        }

        $stmt->close();

        return $skills;
    }

    // --- GET SKILL BY ID ---
    public static function getById($conn, int $id)
    {
        $stmt = $conn->prepare("
        SELECT s.skill_id, s.skill_name, s.description, s.categories_category_id, s.thumbnail,
               s.created_by, u.user_name AS offered_by_name, c.category_name
        FROM skills s
        LEFT JOIN users u ON s.created_by = u.user_id
        LEFT JOIN categories c ON s.categories_category_id = c.category_id
        WHERE s.skill_id = ? 
        LIMIT 1
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        // Mark owned_by_user if current session user matches
        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentUser = $_SESSION['user_id'] ?? null;
        $row['owned_by_user'] = $currentUser && $row['created_by'] == $currentUser;

        return new Skill($row);
    }


    public static function isOwnedByUser($conn, $skillId, $userId)
    {
        $stmt = $conn->prepare("SELECT skill_id FROM skills WHERE skill_id = ? AND created_by = ? LIMIT 1");
        $stmt->bind_param("ii", $skillId, $userId);
        $stmt->execute();
        $stmt->store_result();
        $owned = $stmt->num_rows > 0;
        $stmt->close();
        return $owned;
    }
}
