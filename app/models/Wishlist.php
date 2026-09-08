<?php
declare(strict_types=1);

class Wishlist {
    public static function findById(string $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT w.*, 
                    c.company_name AS client_company, c.email AS client_email,
                    p.name AS project_name
             FROM wishlists w
             JOIN users c ON w.client_id = c.id
             LEFT JOIN projects p ON w.project_id = p.id
             WHERE w.id = ?"
        );
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }

    public static function create(array $data): string {
        $db = Database::getInstance();
        $id = $data['id'] ?? Helper::uuid();
        $content = $data['content'];
        $status = $data['status'] ?? 'PENDING';
        $clientId = $data['client_id'];
        $projectId = $data['project_id'] ?: null; // can be null as optional

        $stmt = $db->prepare(
            "INSERT INTO wishlists (id, content, status, client_id, project_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $id, $content, $status, $clientId, $projectId);
        $stmt->execute();
        return $id;
    }

    public static function updateStatus(string $id, string $status): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE wishlists SET status = ? WHERE id = ?");
        $stmt->bind_param("ss", $status, $id);
        return $stmt->execute();
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        [$where, $types, $values] = self::buildWhere($filters);

        $sql = "SELECT w.*, 
                       c.company_name AS client_company, c.email AS client_email,
                       p.name AS project_name
                FROM wishlists w
                JOIN users c ON w.client_id = c.id
                LEFT JOIN projects p ON w.project_id = p.id
                $where
                ORDER BY w.created_at DESC";

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
                FROM wishlists w
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
            $where .= " AND w.status = ?";
            $types .= "s";
            $values[] = $filters['status'];
        }
        if (isset($filters['client_id']) && $filters['client_id'] !== '') {
            $where .= " AND w.client_id = ?";
            $types .= "s";
            $values[] = $filters['client_id'];
        }
        if (isset($filters['project_id']) && $filters['project_id'] !== '') {
            $where .= " AND w.project_id = ?";
            $types .= "s";
            $values[] = $filters['project_id'];
        }

        return [$where, $types, $values];
    }
}
