<?php
declare(strict_types=1);

class AuditLog {
    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        
        $sql = "SELECT a.*, 
                       u.email AS user_email, u.first_name AS user_first, u.last_name AS user_last, u.role AS user_role, u.company_name AS user_company
                FROM audit_logs a
                JOIN users u ON a.user_id = u.id
                WHERE 1=1";
                
        $types = "";
        $values = [];

        if (isset($filters['action']) && $filters['action'] !== '') {
            $sql .= " AND a.action = ?";
            $types .= "s";
            $values[] = $filters['action'];
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $sql .= " AND a.user_id = ?";
            $types .= "s";
            $values[] = $filters['user_id'];
        }
        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $sql .= " AND a.entity_type = ?";
            $types .= "s";
            $values[] = $filters['entity_type'];
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $sql .= " AND a.created_at >= ?";
            $types .= "s";
            $values[] = $filters['date_from'] . ' 00:00:00';
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $sql .= " AND a.created_at <= ?";
            $types .= "s";
            $values[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " ORDER BY a.created_at DESC";

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
