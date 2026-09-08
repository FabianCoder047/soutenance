<?php
declare(strict_types=1);

class Project {
    public static function findById(string $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.*, 
                    c.company_name AS client_company, c.email AS client_email,
                    u.first_name AS creator_first, u.last_name AS creator_last
             FROM projects p
             JOIN users c ON p.client_id = c.id
             JOIN users u ON p.created_by_id = u.id
             WHERE p.id = ?"
        );
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }

    public static function create(array $data): string {
        $db = Database::getInstance();
        $id = $data['id'] ?? Helper::uuid();
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $status = $data['status'] ?? 'ACTIVE';
        $clientId = $data['client_id'];
        $createdById = $data['created_by_id'];

        $stmt = $db->prepare(
            "INSERT INTO projects (id, name, description, status, client_id, created_by_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $id, $name, $description, $status, $clientId, $createdById);
        $stmt->execute();
        return $id;
    }

    public static function update(string $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [];
        $types = "";
        $values = [];
        
        $editable = ['name', 'description', 'status', 'client_id'];

        foreach ($data as $key => $val) {
            if (in_array($key, $editable, true)) {
                $fields[] = "`$key` = ?";
                $values[] = $val;
                $types .= "s";
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE projects SET " . implode(", ", $fields) . " WHERE id = ?";
        $values[] = $id;
        $types .= "s";

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public static function archive(string $id): bool {
        return self::update($id, ['status' => 'ARCHIVED']);
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        [$where, $types, $values] = self::buildWhere($filters);

        $sql = "SELECT p.*, 
                       c.company_name AS client_company, c.email AS client_email,
                       u.first_name AS creator_first, u.last_name AS creator_last
                FROM projects p
                JOIN users c ON p.client_id = c.id
                JOIN users u ON p.created_by_id = u.id
                $where
                ORDER BY p.created_at DESC";

        if (isset($filters['limit']) && (int)$filters['limit'] > 0) {
            $sql .= " LIMIT ?";
            $types .= "i";
            $values[] = (int)$filters['limit'];
            if (isset($filters['offset']) && (int)$filters['offset'] > 0) {
                $sql .= " OFFSET ?";
                $types .= "i";
                $values[] = (int)$filters['offset'];
            }
        }

        if (empty($values)) {
            $res = $db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countAll(array $filters = []): int {
        $db = Database::getInstance();
        [$where, $types, $values] = self::buildWhere($filters);

        $sql = "SELECT COUNT(*) AS c
                FROM projects p
                JOIN users c ON p.client_id = c.id
                JOIN users u ON p.created_by_id = u.id
                $where";

        if (empty($values)) {
            $res = $db->query($sql);
            return $res ? (int)$res->fetch_assoc()['c'] : 0;
        }

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['c'];
    }

    private static function buildWhere(array $filters): array {
        $where = "WHERE 1=1";
        $types = "";
        $values = [];

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where .= " AND p.status = ?";
            $types .= "s";
            $values[] = $filters['status'];
        }
        if (isset($filters['client_id']) && $filters['client_id'] !== '') {
            $where .= " AND p.client_id = ?";
            $types .= "s";
            $values[] = $filters['client_id'];
        }
        if (isset($filters['created_by_id']) && $filters['created_by_id'] !== '') {
            $where .= " AND p.created_by_id = ?";
            $types .= "s";
            $values[] = $filters['created_by_id'];
        }

        return [$where, $types, $values];
    }
}
