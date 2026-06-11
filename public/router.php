<?php
/**
 * Routeur pour le serveur PHP intégré (obligatoire pour /invitation, /login, etc.).
 *
 * Depuis le dossier public :
 *   php -S localhost:8000 router.php
 */
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($uri !== '/' && $uri !== '' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
