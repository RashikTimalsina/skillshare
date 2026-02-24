<?php
class Skill
{
    public $skill_id;
    public $skill_name;
    public $description;
    public $categories_category_id;
    public $category_name;
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
            // Save thumbnail first
            list($thumbnailPath, $err) = saveSkillThumbnail($file['thumbnail']);
            if ($err) throw new Exception($err);

            $categoryId = !empty($data['categories_category_id']) ? (int)$data['categories_category_id'] : null;

            // Insert skill
            $stmt = $conn->prepare("
            INSERT INTO skills (skill_name, description, categories_category_id, users_user_id, thumbnail, created_at, updated_at)
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

            if (!$stmt->execute()) throw new Exception("Skill insert failed: " . $stmt->error);
            $skillId = $stmt->insert_id;
            $stmt->close();

            // --- Ensure teacher record exists ---
            $stmt = $conn->prepare("SELECT id FROM teachers WHERE users_user_id=? AND skills_skill_id=? LIMIT 1");
            $stmt->bind_param("ii", $teacherId, $skillId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO teachers (users_user_id, skills_skill_id, status) VALUES (?, ?, 'active')");
                $stmt->bind_param("ii", $teacherId, $skillId);
                if (!$stmt->execute()) throw new Exception("Failed to insert into teachers: " . $stmt->error);
                $stmt->close();
            } else {
                $stmt->close();
            }

            // --- Ensure user_skills record exists ---
            $stmt = $conn->prepare("SELECT user_skill_id FROM user_skills WHERE user_id=? AND skill_id=? LIMIT 1");
            $stmt->bind_param("ii", $teacherId, $skillId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO user_skills (user_id, skill_id, role) VALUES (?, ?, 'teacher')");
                $stmt->bind_param("ii", $teacherId, $skillId);
                if (!$stmt->execute()) throw new Exception("Failed to insert into user_skills: " . $stmt->error);
                $stmt->close();
            } else {
                $stmt->close();
            }

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
        $conn->begin_transaction();
        try {
            $categoryId = !empty($data['categories_category_id']) ? (int)$data['categories_category_id'] : null;

            $sql = "UPDATE skills SET skill_name=?, description=?, categories_category_id=?, updated_at=NOW()";
            $params = [$data['skill_name'], $data['description'], $categoryId];
            $types = "ssi";

            // Handle thumbnail update if uploaded
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
            if (!$stmt->execute()) throw new Exception("Failed to update skill: " . $stmt->error);
            $stmt->close();

            // Optional: ensure teacher entry exists in teachers table
            $stmt = $conn->prepare("SELECT id FROM teachers WHERE skills_skill_id=? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                // Insert dummy/active teacher link (if needed, adjust to actual owner)
                if (isset($_SESSION['user_id'])) {
                    $teacherId = $_SESSION['user_id'];
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO teachers (users_user_id, skills_skill_id, status) VALUES (?, ?, 'active')");
                    $stmt->bind_param("ii", $teacherId, $id);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $stmt->close();
            }

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    // --- DELETE SKILL ---
    public static function delete($conn, $id)
    {
        $conn->begin_transaction();
        try {
            // Delete from user_skills first to maintain FK constraints
            $stmt = $conn->prepare("DELETE FROM user_skills WHERE skill_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Delete from teachers table
            $stmt = $conn->prepare("DELETE FROM teachers WHERE skills_skill_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Finally, delete the skill itself
            $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) throw new Exception("Failed to delete skill: " . $stmt->error);
            $stmt->close();

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
    // --- FETCH SKILLS ---
    public static function getAllWithCategory($conn, $role, $userId, $search = '', $sort = 'ASC')
    {
        $search = "%$search%";
        $sql = "
    SELECT 
    s.skill_id,
    s.skill_name,
    s.description,
    s.thumbnail,
    u.user_name AS offered_by_name,
    c.category_name,
    s.categories_category_id,
    s.users_user_id
FROM skills s
LEFT JOIN users u ON s.users_user_id = u.user_id
LEFT JOIN categories c ON s.categories_category_id = c.category_id
WHERE s.skill_name LIKE ? OR s.skill_id LIKE ?
ORDER BY s.skill_id $sort
";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();

        $skills = [];
        while ($row = $result->fetch_assoc()) {
            // Optional: mark if skill belongs to current user
            $row['owned_by_user'] = ($role === 'teacher' && $row['users_user_id'] == $userId);
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
       s.users_user_id,
       u.user_name AS offered_by_name, c.category_name
FROM skills s
LEFT JOIN users u ON s.users_user_id = u.user_id
LEFT JOIN categories c ON s.categories_category_id = c.category_id
WHERE s.skill_id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentUser = $_SESSION['user_id'] ?? null;
        $row['owned_by_user'] = ($currentUser && $row['users_user_id'] == $currentUser);
        return new Skill($row);
    }

    public static function isOwnedByUser($conn, $skillId, $userId)
    {
        $stmt = $conn->prepare("SELECT skill_id FROM skills WHERE skill_id = ? AND users_user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $skillId, $userId);
        $stmt->execute();
        $stmt->store_result();
        $owned = $stmt->num_rows > 0;
        $stmt->close();
        return $owned;
    }
}
