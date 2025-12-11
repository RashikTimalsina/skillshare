<?php
class Skill
{
    public $skill_id;
    public $skill_name;
    public $description;
    public $categories_category_id;
    public $created_at;

    public function __construct($data = [])
    {
        $this->skill_id = $data['skill_id'] ?? null;
        $this->skill_name = $data['skill_name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->categories_category_id = $data['categories_category_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM skills WHERE skill_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Skill($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM skills ORDER BY created_at DESC");
        $skills = [];
        while ($row = $result->fetch_assoc()) {
            $skills[] = new Skill($row);
        }
        return $skills;
    }

    // public static function getTrending($conn, $limit = 4)
    // {
    //     $stmt = $conn->prepare("SELECT * FROM skills ORDER BY created_at DESC LIMIT ?");
    //     $stmt->bind_param("i", $limit);
    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     $skills = [];
    //     while ($row = $result->fetch_assoc()) {
    //         $skills[] = new Skill($row);
    //     }
    //     $stmt->close();
    //     return $skills;
    // }

    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO skills (skill_name, description, categories_category_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $data['skill_name'], $data['description'], $data['categories_category_id']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE skills SET skill_name=?, description=?, categories_category_id=? WHERE skill_id=?");
        $stmt->bind_param("ssii", $data['skill_name'], $data['description'], $data['categories_category_id'], $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
