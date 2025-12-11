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

    public static function create($conn, $data, $userId = null, $userName = null)
    {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $data['category_name'], $data['description']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        // Auto log if user info provided
        if ($userId && $userName) {
            logActivity($conn, $userId, $userName, "Added new category: {$data['category_name']}");
        }

        return $id;
    }

    public static function update($conn, $id, $data, $userId = null, $userName = null)
    {
        $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
        $stmt->bind_param("ssi", $data['category_name'], $data['description'], $id);
        $stmt->execute();
        $stmt->close();

        if ($userId && $userName) {
            logActivity($conn, $userId, $userName, "Edited category ID $id: {$data['category_name']}");
        }

        return true;
    }

    public static function delete($conn, $id, $userId = null, $userName = null)
    {
        $category = self::getById($conn, $id);
        if (!$category) return false;

        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        if ($userId && $userName) {
            logActivity($conn, $userId, $userName, "Deleted category ID $id: {$category->category_name}");
        }

        return true;
    }
}
