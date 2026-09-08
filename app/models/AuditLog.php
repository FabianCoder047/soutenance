<?php
declare(strict_types=1);

class AuditLog {
    private static function buildWhere(array $filters): array {
        $where = "WHERE 1=1";
        $types = "";
        $values = [];

        if (isset($filters['action']) && $filters['action'] !== '') {
            $where .= " AND a.action = ?";
            $types .= "s";
            $values[] = $filters['action'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $where .= " AND a.user_id = ?";
            $types .= "s";
            $values[] = $filters['user_id'];
        }
        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $where .= " AND a.entity_type = ?";
            $types .= "s";
            $values[] = $filters['entity_type'];
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $where .= " AND a.created_at >= ?";
            $types .= "s";
            $values[] = $filters['date_from'] . ' 00:00:00';
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $where .= " AND a.created_at <= ?";
            $types .= "s";
            $values[] = $filters['date_to'] . ' 23:59:59';
        }

        return [$where, $types, $values];
    }

    public static function countAll(array $filters = []): int {
        $db = Database::getInstance();
        [$where, $types, $values] = self::buildWhere($filters);

        $sql = "SELECT COUNT(*) AS c
                FROM audit_logs a
                JOIN users u ON a.user_id = u.id
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

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        [$where, $types, $values] = self::buildWhere($filters);

        $sql = "SELECT a.*, 
                       u.email AS user_email, u.first_name AS user_first, u.last_name AS user_last, u.role AS user_role, u.company_name AS user_company
                FROM audit_logs a
                JOIN users u ON a.user_id = u.id
                $where
                ORDER BY a.created_at DESC";

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
}
