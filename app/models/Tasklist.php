<?php
declare(strict_types=1);

class Tasklist {
    public const STATUS_LABELS = [
        'DRAFT' => 'Brouillon',
        'PENDING' => 'En attente',
        'IN_PROGRESS' => 'En cours',
        'CLIENT_FILLED' => 'Réponse client',
        'DONE' => 'Traité',
    ];

    public const STATUS_CLASSES = [
        'DRAFT' => 'badge-draft',
        'PENDING' => 'badge-pending',
        'IN_PROGRESS' => 'badge-in_progress',
        'CLIENT_FILLED' => 'badge-client_filled',
        'DONE' => 'badge-done',
    ];

    public static function statusLabel(string $status): string {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function statusClass(string $status): string {
        return self::STATUS_CLASSES[$status] ?? 'badge-pending';
    }

    public static function isDraft(array $tasklist): bool {
        return ($tasklist['status'] ?? '') === 'DRAFT';
    }

    public static function isVisibleToClient(array $tasklist): bool {
        return !self::isDraft($tasklist);
    }

    public static function hasClientResponded(array $tasklist): bool {
        return !empty($tasklist['client_filled'])
            || ($tasklist['status'] ?? '') === 'CLIENT_FILLED';
    }

    public static function canClientRespond(array $user, array $tasklist): bool {
        if ($user['role'] !== 'CLIENT') {
            return false;
        }
        if (!self::canView($user, $tasklist)) {
            return false;
        }
        if (($tasklist['status'] ?? '') === 'DONE') {
            return false;
        }
        return !self::hasClientResponded($tasklist);
    }

    public static function canDelete(array $user, array $tasklist): bool {
        if ($user['role'] === 'ADMIN') {
            return true;
        }
        if ($user['role'] === 'DEV') {
            return self::canEdit($user, $tasklist);
        }
        return false;
    }

    public static function canEdit(array $user, array $tasklist): bool {
        if (!self::isDraft($tasklist)) {
            return false;
        }
        if ($user['role'] === 'ADMIN') {
            return true;
        }
        if ($user['role'] === 'DEV') {
            return ($tasklist['assigned_to_id'] ?? '') === $user['id']
                || ($tasklist['created_by_id'] ?? '') === $user['id'];
        }
        return false;
    }

    public static function canView(array $user, array $tasklist): bool {
        if ($user['role'] === 'ADMIN') {
            return true;
        }
        if ($user['role'] === 'CLIENT') {
            return ($tasklist['project_client_id'] ?? '') === $user['id']
                && self::isVisibleToClient($tasklist);
        }
        if ($user['role'] === 'DEV') {
            return ($tasklist['assigned_to_id'] ?? '') === $user['id']
                || ($tasklist['created_by_id'] ?? '') === $user['id'];
        }
        return false;
    }

    public static function findById(string $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT t.*, 
                    p.name AS project_name, p.client_id AS project_client_id,
                    c.company_name AS client_company,
                    u.first_name AS creator_first, u.last_name AS creator_last,
                    a.first_name AS assignee_first, a.last_name AS assignee_last
             FROM tasklists t
             JOIN projects p ON t.project_id = p.id
             JOIN users c ON p.client_id = c.id
             JOIN users u ON t.created_by_id = u.id
             LEFT JOIN users a ON t.assigned_to_id = a.id
             WHERE t.id = ?"
        );
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }

    public static function create(array $data): string {
        $db = Database::getInstance();
        $id = $data['id'] ?? Helper::uuid();
        $title = $data['title'];
        $description = $data['description'] ?? null;
        $status = $data['status'] ?? 'DRAFT';
        $projectId = $data['project_id'];
        $createdById = $data['created_by_id'];
        $assignedToId = (!empty($data['assigned_to_id'])) ? $data['assigned_to_id'] : $createdById; // fallback to creator if empty
        $clientFilled = $data['client_filled'] ?? 0;
        $clientResponse = $data['client_response'] ?? null;
        $treatedAt = $data['treated_at'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO tasklists (id, title, description, status, project_id, created_by_id, assigned_to_id, client_filled, client_response, treated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $cFilled = (int)$clientFilled;
        $stmt->bind_param(
            "sssssssiss",
            $id, $title, $description, $status, $projectId, $createdById, $assignedToId, $cFilled, $clientResponse, $treatedAt
        );
        $stmt->execute();
        return $id;
    }

    public static function update(string $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [];
        $types = "";
        $values = [];

        $editable = [
            'title', 'description', 'status', 'project_id', 
            'assigned_to_id', 'client_filled', 'client_response', 'treated_at'
        ];

        foreach ($data as $key => $val) {
            if (in_array($key, $editable, true)) {
                $fields[] = "`$key` = ?";
                $values[] = $val;
                if (is_int($val)) {
                    $types .= "i";
                } else {
                    $types .= "s";
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE tasklists SET " . implode(", ", $fields) . " WHERE id = ?";
        $values[] = $id;
        $types .= "s";

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public static function assign(string $id, string $assignedToId): bool {
        return self::update($id, ['assigned_to_id' => $assignedToId]);
    }

    public static function delete(string $id): bool {
        self::deleteUploadDirectory($id);

        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM tasklists WHERE id = ?');
        $stmt->bind_param('s', $id);
        return $stmt->execute();
    }

    private static function deleteUploadDirectory(string $tasklistId): void {
        $dir = dirname(__DIR__, 2) . '/storage/uploads/tasklists/' . $tasklistId;
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public static function publish(string $id): bool {
        $tasklist = self::findById($id);
        if (!$tasklist || $tasklist['status'] !== 'DRAFT') {
            return false;
        }
        return self::update($id, ['status' => 'PENDING']);
    }

    public static function treat(string $id): bool {
        return self::update($id, [
            'status' => 'DONE',
            'treated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function clientFill(string $id, ?string $response = null): bool {
        return self::update($id, [
            'client_response' => $response,
            'client_filled' => 1,
            'status' => 'CLIENT_FILLED'
        ]);
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        [$where, $types, $values] = self::buildWhere($filters);

        $sql = "SELECT t.*, 
                       p.name AS project_name, p.client_id AS project_client_id,
                       c.company_name AS client_company,
                       u.first_name AS creator_first, u.last_name AS creator_last,
                       a.first_name AS assignee_first, a.last_name AS assignee_last
                FROM tasklists t
                JOIN projects p ON t.project_id = p.id
                JOIN users c ON p.client_id = c.id
                JOIN users u ON t.created_by_id = u.id
                LEFT JOIN users a ON t.assigned_to_id = a.id
                $where
                ORDER BY t.created_at DESC";

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
                FROM tasklists t
                JOIN projects p ON t.project_id = p.id
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

        if (isset($filters['project_id']) && $filters['project_id'] !== '') {
            $where .= " AND t.project_id = ?";
            $types .= "s";
            $values[] = $filters['project_id'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where .= " AND t.status = ?";
            $types .= "s";
            $values[] = $filters['status'];
        }
        if (isset($filters['assigned_to_id']) && $filters['assigned_to_id'] !== '') {
            $where .= " AND t.assigned_to_id = ?";
            $types .= "s";
            $values[] = $filters['assigned_to_id'];
        }
        if (isset($filters['dev_access_id']) && $filters['dev_access_id'] !== '') {
            $where .= " AND (t.assigned_to_id = ? OR t.created_by_id = ?)";
            $types .= "ss";
            $values[] = $filters['dev_access_id'];
            $values[] = $filters['dev_access_id'];
        }
        if (isset($filters['client_id']) && $filters['client_id'] !== '') {
            $where .= " AND p.client_id = ?";
            $types .= "s";
            $values[] = $filters['client_id'];
        }
        if (!empty($filters['exclude_draft'])) {
            $where .= " AND t.status != 'DRAFT'";
        }

        return [$where, $types, $values];
    }
}
