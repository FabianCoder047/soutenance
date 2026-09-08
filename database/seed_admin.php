<?php
declare(strict_types=1);

/**
 * Réinsère le compte admin par défaut.
 * Email : codingfabio20@gmail.com | Mot de passe : azerty123
 * Usage : php database/seed_admin.php
 */

$root = dirname(__DIR__);
if (file_exists($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$name = $_ENV['DB_NAME'] ?? 'emedia_support';

$db = new mysqli($host, $user, $pass, $name);
if ($db->connect_error) {
    fwrite(STDERR, "Connexion impossible : {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$sql = <<<'SQL'
INSERT INTO users (id, email, role, first_name, last_name, password_hash, status, profile_complete)
VALUES (
    'f0000000-0000-0000-0000-000000000001',
    'codingfabio20@gmail.com',
    'ADMIN',
    'Fabio',
    'DAB',
    '$2y$12$3dnJqbSkuakcZ8C/RBwnserncKKjGFN45UChdBmoByFtBlIafr/Ee',
    'ACTIVE',
    1
)
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    role = VALUES(role),
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    password_hash = VALUES(password_hash),
    status = VALUES(status),
    profile_complete = VALUES(profile_complete),
    invite_token = NULL,
    invite_expiry = NULL
SQL;

if (!$db->query($sql)) {
    fwrite(STDERR, "Erreur SQL : {$db->error}\n");
    exit(1);
}

echo "Compte admin recréé avec succès.\n";
echo "  Email    : codingfabio20@gmail.com\n";
echo "  Mot de passe : azerty123\n";
