<?php
class Certificate
{
    public $id;
    public $learner_id;
    public $course_id;
    public $certificate_code;
    public $issued_date;
    public $expiry_date;
    public $status;
    public $issued_by;
    public $remarks;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->learner_id = $data['learner_id'] ?? null;
        $this->course_id = $data['course_id'] ?? null;
        $this->certificate_code = $data['certificate_code'] ?? null;
        $this->issued_date = $data['issued_date'] ?? null;
        $this->expiry_date = $data['expiry_date'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->issued_by = $data['issued_by'] ?? null;
        $this->remarks = $data['remarks'] ?? null;
    }

    public static function getByLearnerId($conn, $learner_id)
    {
        $stmt = $conn->prepare("SELECT * FROM certificates WHERE learner_id=? ORDER BY issued_date DESC");
        $stmt->bind_param("i", $learner_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $certificates = [];
        while ($row = $res->fetch_assoc()) {
            $certificates[] = new Certificate($row);
        }
        $stmt->close();
        return $certificates;
    }

    public static function getById($conn, $id)
    {
        $stmt = $conn->prepare("SELECT * FROM certificates WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? new Certificate($result) : null;
    }

    public static function getAll($conn)
    {
        $result = $conn->query("SELECT * FROM certificates ORDER BY issued_date DESC");
        $certificates = [];
        while ($row = $result->fetch_assoc()) {
            $certificates[] = new Certificate($row);
        }
        return $certificates;
    }

    public static function create($conn, $data)
    {
        $stmt = $conn->prepare("INSERT INTO certificates (learner_id, course_id, certificate_code, issued_date, expiry_date, status, issued_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iissssis",
            $data['learner_id'],
            $data['course_id'],
            $data['certificate_code'],
            $data['issued_date'],
            $data['expiry_date'],
            $data['status'],
            $data['issued_by'],
            $data['remarks']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function update($conn, $id, $data)
    {
        $stmt = $conn->prepare("UPDATE certificates SET learner_id=?, course_id=?, certificate_code=?, issued_date=?, expiry_date=?, status=?, issued_by=?, remarks=? WHERE id=?");
        $stmt->bind_param(
            "iissssisi",
            $data['learner_id'],
            $data['course_id'],
            $data['certificate_code'],
            $data['issued_date'],
            $data['expiry_date'],
            $data['status'],
            $data['issued_by'],
            $data['remarks'],
            $id
        );
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public static function delete($conn, $id)
    {
        $stmt = $conn->prepare("DELETE FROM certificates WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
}
