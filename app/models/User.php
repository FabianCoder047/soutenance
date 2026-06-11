<?php
declare(strict_types=1);

class User {
    public static function findById(string $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }

    public static function findByInviteToken(string $token): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE invite_token = ? AND invite_expiry > NOW() AND status = 'PENDING'");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ?: null;
    }

    public static function create(array $data): string {
        $db = Database::getInstance();
        $id = $data['id'] ?? Helper::uuid();
        $email = $data['email'];
        $role = $data['role'];
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $companyName = $data['company_name'] ?? null;
        $address = $data['address'] ?? null;
        $passwordHash = $data['password_hash'] ?? null;
        $status = $data['status'] ?? 'PENDING';
        $inviteToken = $data['invite_token'] ?? null;
        $inviteExpiry = $data['invite_expiry'] ?? null;
        $profileComplete = $data['profile_complete'] ?? 0;

        $stmt = $db->prepare(
            "INSERT INTO users (id, email, role, first_name, last_name, company_name, address, password_hash, status, invite_token, invite_expiry, profile_complete)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $pComplete = (int)$profileComplete;
        $stmt->bind_param(
            "sssssssssssi",
            $id, $email, $role, $firstName, $lastName, $companyName, $address, $passwordHash, $status, $inviteToken, $inviteExpiry, $pComplete
        );
        $stmt->execute();
        return $id;
    }

    public static function update(string $id, array $data): bool {
        $db = Database::getInstance();
        
        $fields = [];
        $types = "";
        $values = [];
        
        // Editable fields (email should be immutable as per spec 8.1: "L'email est immutable après création du compte")
        $editable = [
            'first_name', 'last_name', 'company_name', 'address', 
            'password_hash', 'status', 'invite_token', 'invite_expiry', 'profile_complete'
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

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $values[] = $id;
        $types .= "s";

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE 1=1";
        $types = "";
        $values = [];

        if (!empty($filters['role'])) {
            $sql .= " AND role = ?";
            $types .= "s";
            $values[] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $types .= "s";
            $values[] = $filters['status'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (empty($values)) {
            $res = $db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getClients(): array {
        return self::getAll(['role' => 'CLIENT']);
    }

    public static function getDevelopers(): array {
        $db = Database::getInstance();
        // Devs and Admins can be assigned tasklists as creators or assignees
        $stmt = $db->prepare("SELECT * FROM users WHERE role IN ('DEV', 'ADMIN') AND status = 'ACTIVE' ORDER BY first_name, last_name");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
