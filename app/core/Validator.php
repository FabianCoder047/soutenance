<?php
declare(strict_types=1);

class Validator {
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Password validation rule: MDP ≥ 8 chars, 1 maj + 1 chiffre
     */
    public static function password(string $password): bool {
        if (strlen($password) < 8) {
            return false;
        }
        // At least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        // At least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        return true;
    }

    public static function required(array $data, array $fields): array {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = "Le champ " . str_replace('_', ' ', $field) . " est obligatoire.";
            }
        }
        return $errors;
    }
}
