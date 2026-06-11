<?php
declare(strict_types=1);

class Audit {
    public static function logAction(
        string $action,
        string $entityType,
        string $entityId,
        string $userId,
        array  $metadata = []
    ): void {
        $db = Database::getInstance();
        $id = Helper::uuid();
        $metaJson = !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $db->prepare(
            "INSERT INTO audit_logs (id, action, entity_type, entity_id, user_id, metadata)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $id, $action, $entityType, $entityId, $userId, $metaJson);
        $stmt->execute();
    }
}
