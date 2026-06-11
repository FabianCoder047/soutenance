<?php
declare(strict_types=1);

class Auth {
    public static function check(): bool {
        Session::start();
        return Session::has('user_id');
    }

    public static function user(): ?array {
        Session::start();
        return Session::get('user');
    }

    public static function require(string ...$roles): void {
        Session::start();
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
        $user = self::user();
        if ($user === null || ($user['status'] ?? '') === 'SUSPENDED') {
            http_response_code(403);
            $viewsPath = dirname(__DIR__) . '/views/errors/403.php';
            if (file_exists($viewsPath)) {
                include $viewsPath;
            } else {
                echo '<h1>403 Forbidden</h1><p>Votre compte est suspendu ou vous n\'avez pas accès à cette page.</p>';
            }
            exit;
        }
        if (!empty($roles) && !in_array($user['role'] ?? '', $roles, true)) {
            http_response_code(403);
            $viewsPath = dirname(__DIR__) . '/views/errors/403.php';
            if (file_exists($viewsPath)) {
                include $viewsPath;
            } else {
                echo '<h1>403 Forbidden</h1><p>Accès refusé.</p>';
            }
            exit;
        }
    }
}
