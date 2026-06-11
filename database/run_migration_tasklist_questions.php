<?php
declare(strict_types=1);

/**
 * Crée les tables tasklist_questions, options, answers, fichiers.
 * Usage : php database/run_migration_tasklist_questions.php
 */

$root = dirname(__DIR__);

if (file_exists($root . '/.env')) {
    $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
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

$sqlFile = __DIR__ . '/migration_tasklist_questions.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "Fichier introuvable : {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
$db = Database::getInstance();

if (!$db->multi_query($sql)) {
    fwrite(STDERR, "Erreur migration : " . $db->error . "\n");
    exit(1);
}

do {
    if ($result = $db->store_result()) {
        $result->free();
    }
} while ($db->more_results() && $db->next_result());

if ($db->errno) {
    fwrite(STDERR, "Erreur migration : " . $db->error . "\n");
    exit(1);
}

$tables = [
    'tasklist_questions',
    'tasklist_question_options',
    'tasklist_answers',
    'tasklist_answer_options',
    'tasklist_answer_files',
];

foreach ($tables as $table) {
    $check = $db->query("SHOW TABLES LIKE '{$table}'");
    if (!$check || $check->num_rows === 0) {
        fwrite(STDERR, "Table manquante après migration : {$table}\n");
        exit(1);
    }
}

echo "Migration OK : tables tasklist (questions, options, réponses, fichiers) créées.\n";
