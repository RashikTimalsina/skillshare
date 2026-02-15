<?php
class Category
{
    public $category_id;
    public $category_name;
    public $description;

    public function __construct($data = [])
    {
        $this->category_id = $data['category_id'] ?? null;
        $this->category_name = $data['category_name'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Category($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = new Category($row);
        }
        return $categories;
    }

    private static function existsByName($conn, $name, $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name=? AND category_id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name=?");
            $stmt->bind_param("s", $name);
        }
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public static function create($conn, $data, $userId = null, $userName = null)
    {
        $name = trim($data['category_name']);
        if (self::existsByName($conn, $name)) {
            return false; // Duplicate
        }

        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $data['description']);
        $success = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        if ($success && $userId) {
            logActivity($conn, $userId, "Added new category: {$name}");
        }


        return $success ? $id : false;
    }

    public static function update($conn, $id, $data, $userId = null, $userName = null)
    {
        $name = trim($data['category_name']);
        if (self::existsByName($conn, $name, $id)) {
            return false; // Duplicate
        }

        $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
        $stmt->bind_param("ssi", $name, $data['description'], $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success && $userId) {
            logActivity($conn, $userId, "Edited category ID $id: {$name}");
        }

        return $success;
    }

    public static function delete($conn, $id, $userId = null, $userName = null)
    {
        $category = self::getById($conn, $id);
        if (!$category) return false;

        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success && $userId ) {
            logActivity($conn, $userId,  "Deleted category ID $id: {$category->category_name}");
        }

        return $success;
    }
}
