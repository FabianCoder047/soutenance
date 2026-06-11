<?php
declare(strict_types=1);

class Helper {
    public static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    /**
     * URL absolue de l'application (emails, liens d'invitation).
     * En développement, utilise l'hôte/port de la requête courante si disponible.
     */
    public static function appUrl(string $path = ''): string {
        $appConfig = include dirname(__DIR__, 2) . '/config/app.php';
        $base = rtrim((string)($appConfig['url'] ?? ''), '/');
        $env = $appConfig['env'] ?? 'development';

        if ($env === 'development' && !empty($_SERVER['HTTP_HOST'])) {
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $base = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        }

        if ($base === '') {
            $base = 'http://localhost:8000';
        }

        $basePath = rtrim((string)($_ENV['APP_BASE_PATH'] ?? ''), '/');

        if ($path !== '' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $base . $basePath . $path;
    }

    public static function formatDateTime(string $datetime): string {
        return date('d/m/Y H:i', strtotime($datetime));
    }

    public static function formatDate(string $datetime): string {
        return date('d/m/Y', strtotime($datetime));
    }

    public static function formatTime(string $datetime): string {
        return date('H:i', strtotime($datetime));
    }

    public static function datetimeIso(string $datetime): string {
        $ts = strtotime($datetime);
        return $ts !== false ? date('c', $ts) : '';
    }
}
