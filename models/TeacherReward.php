<?php
class TeacherReward
{
    public static function award(
        mysqli $conn,
        int $teacherId,
        int $points,
        string $reason,
        int $adminId
    ): bool {
        $stmt = $conn->prepare("
        INSERT INTO teacher_rewards 
        (teacher_id, points, reason, awarded_by, awarded_date, status)
        VALUES (?, ?, ?, ?, NOW(), 'active')
    ");

        $stmt->bind_param(
            "iisi",
            $teacherId,
            $points,
            $reason,
            $adminId
        );

        return $stmt->execute();
    }


    public static function revoke(mysqli $conn, int $rewardId): bool
    {
        $stmt = $conn->prepare("
            UPDATE teacher_rewards 
            SET status = 'revoked' 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $rewardId);
        return $stmt->execute();
    }

    public static function getActiveByTeacher(mysqli $conn, int $teacherId): array
    {
        $stmt = $conn->prepare("
            SELECT * FROM teacher_rewards
            WHERE teacher_id = ?
              AND status = 'active'
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            ORDER BY awarded_date DESC
        ");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getAllByTeacher(mysqli $conn, int $teacherId): array
    {
        $stmt = $conn->prepare("
            SELECT * FROM teacher_rewards
            WHERE teacher_id = ?
            ORDER BY awarded_date DESC
        ");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    //Admin get All rewards
    public static function getAll(mysqli $conn): array
    {
        $sql = "
            SELECT 
                tr.id,
                tr.points,
                tr.reason,
                tr.awarded_date,
                tr.expiry_date,
                tr.status,
                u.user_name AS teacher_name
            FROM teacher_rewards tr
            JOIN teachers t ON t.id = tr.teacher_id
            JOIN users u ON u.user_id = t.users_user_id
            ORDER BY tr.awarded_date DESC
        ";

        $res = $conn->query($sql);

        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}
