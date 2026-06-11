<?php
declare(strict_types=1);

$root = dirname(__DIR__);

if (file_exists($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1], " \t\"'");
        }
    }
}

require_once $root . '/app/core/Database.php';

$db = Database::getInstance();
$sql = file_get_contents(__DIR__ . '/migration_tasklist_draft.sql');

if (!$db->query($sql)) {
    fwrite(STDERR, "Erreur : " . $db->error . "\n");
    exit(1);
}

echo "Migration OK : statut DRAFT ajouté aux tasklists.\n";
