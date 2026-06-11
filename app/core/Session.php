<?php
declare(strict_types=1);

class Session {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session max lifetime if needed, default is fine
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        self::start();
        session_destroy();
        $_SESSION = [];
    }

    public static function getCsrfToken(): string {
        self::start();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        if ($token === null) {
            return false;
        }
        $stored = self::get('csrf_token');
        return $stored !== null && hash_equals($stored, $token);
    }
}
