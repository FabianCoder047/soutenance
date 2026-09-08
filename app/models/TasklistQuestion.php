<?php
declare(strict_types=1);

class TasklistQuestion {
    public const TYPES = [
        'SINGLE_CHOICE' => 'Choix unique',
        'MULTIPLE_CHOICE' => 'Choix multiples',
        'LONG_TEXT' => 'Texte long',
        'FILE_UPLOAD' => 'Fichier (image ou document)',
    ];

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public static function typeLabel(string $type): string {
        return self::TYPES[$type] ?? $type;
    }

    public static function deleteByTasklistId(string $tasklistId): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM tasklist_questions WHERE tasklist_id = ?");
        $stmt->bind_param('s', $tasklistId);
        $stmt->execute();
    }

    public static function replaceBatch(string $tasklistId, array $questions): void {
        self::deleteByTasklistId($tasklistId);
        self::createBatch($tasklistId, $questions);
    }

    public static function createBatch(string $tasklistId, array $questions): void {
        $db = Database::getInstance();

        foreach ($questions as $index => $question) {
            $questionId = Helper::uuid();
            $label = trim($question['label'] ?? '');
            $type = $question['type'] ?? '';
            $required = !empty($question['required']) ? 1 : 0;
            $sortOrder = (int)($question['sort_order'] ?? $index);

            if ($label === '' || !isset(self::TYPES[$type])) {
                continue;
            }

            $stmt = $db->prepare(
                "INSERT INTO tasklist_questions (id, tasklist_id, label, type, required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssii', $questionId, $tasklistId, $label, $type, $required, $sortOrder);
            $stmt->execute();

            if (in_array($type, ['SINGLE_CHOICE', 'MULTIPLE_CHOICE'], true)) {
                $options = $question['options'] ?? [];
                foreach ($options as $optIndex => $optionLabel) {
                    $optionLabel = trim((string)$optionLabel);
                    if ($optionLabel === '') {
                        continue;
                    }

                    $optionId = Helper::uuid();
                    $optSort = (int)$optIndex;
                    $optStmt = $db->prepare(
                        "INSERT INTO tasklist_question_options (id, question_id, label, sort_order)
                         VALUES (?, ?, ?, ?)"
                    );
                    $optStmt->bind_param('sssi', $optionId, $questionId, $optionLabel, $optSort);
                    $optStmt->execute();
                }
            }
        }
    }

    public static function getByTasklistId(string $tasklistId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM tasklist_questions WHERE tasklist_id = ? ORDER BY sort_order ASC, created_at ASC"
        );
        $stmt->bind_param('s', $tasklistId);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($questions as &$question) {
            $question['options'] = self::getOptions($question['id']);
        }
        unset($question);

        return $questions;
    }

    public static function getOptions(string $questionId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM tasklist_question_options WHERE question_id = ? ORDER BY sort_order ASC"
        );
        $stmt->bind_param('s', $questionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countByTasklistId(string $tasklistId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM tasklist_questions WHERE tasklist_id = ?");
        $stmt->bind_param('s', $tasklistId);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['c'];
    }

    public static function countUnanswered(string $tasklistId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS c
             FROM tasklist_questions q
             WHERE q.tasklist_id = ?
               AND NOT EXISTS (
                   SELECT 1
                   FROM tasklist_answers a
                   WHERE a.question_id = q.id
                     AND a.tasklist_id = q.tasklist_id
                     AND (
                         (q.type = 'LONG_TEXT' AND a.text_value IS NOT NULL AND TRIM(a.text_value) <> '')
                         OR (q.type IN ('SINGLE_CHOICE', 'MULTIPLE_CHOICE') AND EXISTS (
                             SELECT 1
                             FROM tasklist_answer_options ao
                             JOIN tasklist_question_options o ON o.id = ao.option_id
                             WHERE ao.answer_id = a.id AND o.question_id = q.id))
                         OR (q.type = 'FILE_UPLOAD' AND EXISTS (
                             SELECT 1 FROM tasklist_answer_files f WHERE f.answer_id = a.id))
                     )
               )"
        );
        $stmt->bind_param('s', $tasklistId);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['c'];
    }

    public static function getAnswersForTasklist(string $tasklistId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT a.*, q.label AS question_label, q.type AS question_type, q.required AS question_required
             FROM tasklist_answers a
             JOIN tasklist_questions q ON q.id = a.question_id
             WHERE a.tasklist_id = ?
             ORDER BY q.sort_order ASC, q.created_at ASC"
        );
        $stmt->bind_param('s', $tasklistId);
        $stmt->execute();
        $answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($answers as &$answer) {
            if (in_array($answer['question_type'], ['SINGLE_CHOICE', 'MULTIPLE_CHOICE'], true)) {
                $answer['selected_options'] = self::getSelectedOptions($answer['id']);
            }
            if ($answer['question_type'] === 'FILE_UPLOAD') {
                $answer['files'] = self::getFiles($answer['id']);
            }
        }
        unset($answer);

        return $answers;
    }

    private static function getSelectedOptions(string $answerId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT o.*
             FROM tasklist_answer_options ao
             JOIN tasklist_question_options o ON o.id = ao.option_id
             WHERE ao.answer_id = ?
             ORDER BY o.sort_order ASC"
        );
        $stmt->bind_param('s', $answerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getFiles(string $answerId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM tasklist_answer_files WHERE answer_id = ? ORDER BY created_at ASC"
        );
        $stmt->bind_param('s', $answerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function findFileById(string $fileId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT f.*, a.tasklist_id, t.project_id, p.client_id
             FROM tasklist_answer_files f
             JOIN tasklist_answers a ON a.id = f.answer_id
             JOIN tasklists t ON t.id = a.tasklist_id
             JOIN projects p ON p.id = t.project_id
             WHERE f.id = ?"
        );
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public static function saveAnswers(string $tasklistId, array $questions, array $postAnswers, array $files): array {
        $errors = [];

        foreach ($questions as $question) {
            $questionId = $question['id'];
            $type = $question['type'];
            $required = true;
            $value = $postAnswers[$questionId] ?? null;

            if ($type === 'MULTIPLE_CHOICE' && !is_array($value)) {
                $value = $value !== null && $value !== '' ? [$value] : [];
            }

            if ($required) {
                if ($type === 'LONG_TEXT' && trim((string)$value) === '') {
                    $errors[] = "La question « {$question['label']} » est obligatoire.";
                    continue;
                }
                if ($type === 'SINGLE_CHOICE' && empty($value)) {
                    $errors[] = "Veuillez sélectionner une option pour « {$question['label']} ».";
                    continue;
                }
                if ($type === 'MULTIPLE_CHOICE' && (empty($value) || !is_array($value))) {
                    $errors[] = "Veuillez sélectionner au moins une option pour « {$question['label']} ».";
                    continue;
                }
                if ($type === 'FILE_UPLOAD') {
                    $upload = $files[$questionId] ?? null;
                    $hasNewUpload = $upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    $existing = self::findAnswer($tasklistId, $questionId);
                    $hasExistingFiles = $existing && !empty(self::getFiles($existing['id']));

                    if (!$hasNewUpload && !$hasExistingFiles) {
                        $errors[] = "Veuillez joindre un fichier pour « {$question['label']} ».";
                        continue;
                    }
                }
            }

            if ($type === 'FILE_UPLOAD') {
                $upload = $files[$questionId] ?? null;
                if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    $errors[] = "Erreur lors de l'envoi du fichier pour « {$question['label']} ».";
                    continue;
                }

                $fileError = self::storeUploadedFile($tasklistId, $questionId, $upload);
                if ($fileError !== null) {
                    $errors[] = $fileError;
                }
                continue;
            }

            if ($type === 'LONG_TEXT') {
                $text = trim((string)$value);
                if ($text === '') {
                    continue;
                }
                self::upsertTextAnswer($tasklistId, $questionId, $text);
                continue;
            }

            if ($type === 'SINGLE_CHOICE') {
                if (empty($value)) {
                    continue;
                }
                self::upsertChoiceAnswer($tasklistId, $questionId, [(string)$value]);
                continue;
            }

            if ($type === 'MULTIPLE_CHOICE') {
                if (empty($value) || !is_array($value)) {
                    continue;
                }
                $optionIds = array_map('strval', $value);
                self::upsertChoiceAnswer($tasklistId, $questionId, $optionIds);
            }
        }

        return $errors;
    }

    private static function upsertTextAnswer(string $tasklistId, string $questionId, string $text): void {
        $db = Database::getInstance();
        $existing = self::findAnswer($tasklistId, $questionId);

        if ($existing) {
            $stmt = $db->prepare("UPDATE tasklist_answers SET text_value = ? WHERE id = ?");
            $stmt->bind_param('ss', $text, $existing['id']);
            $stmt->execute();
            return;
        }

        $answerId = Helper::uuid();
        $stmt = $db->prepare(
            "INSERT INTO tasklist_answers (id, question_id, tasklist_id, text_value) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssss', $answerId, $questionId, $tasklistId, $text);
        $stmt->execute();
    }

    private static function upsertChoiceAnswer(string $tasklistId, string $questionId, array $optionIds): void {
        $db = Database::getInstance();
        $validOptionIds = array_column(self::getOptions($questionId), 'id');
        $optionIds = array_values(array_intersect($optionIds, $validOptionIds));

        if (empty($optionIds)) {
            return;
        }

        $existing = self::findAnswer($tasklistId, $questionId);
        $answerId = $existing['id'] ?? Helper::uuid();

        if ($existing) {
            $del = $db->prepare("DELETE FROM tasklist_answer_options WHERE answer_id = ?");
            $del->bind_param('s', $answerId);
            $del->execute();
        } else {
            $stmt = $db->prepare(
                "INSERT INTO tasklist_answers (id, question_id, tasklist_id, text_value) VALUES (?, ?, ?, NULL)"
            );
            $stmt->bind_param('sss', $answerId, $questionId, $tasklistId);
            $stmt->execute();
        }

        $insert = $db->prepare("INSERT INTO tasklist_answer_options (answer_id, option_id) VALUES (?, ?)");
        foreach ($optionIds as $optionId) {
            $insert->bind_param('ss', $answerId, $optionId);
            $insert->execute();
        }
    }

    private static function storeUploadedFile(string $tasklistId, string $questionId, array $upload): ?string {
        $originalName = basename((string)($upload['name'] ?? 'fichier'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return "Format de fichier non autorisé pour « {$originalName} ».";
        }

        if (($upload['size'] ?? 0) > self::MAX_FILE_SIZE) {
            return "Le fichier « {$originalName} » dépasse la taille maximale de 10 Mo.";
        }

        $storageDir = self::uploadDirectory($tasklistId);
        if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
            return "Impossible de préparer le dossier de stockage.";
        }

        $storedName = Helper::uuid() . '.' . $extension;
        $storedPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($upload['tmp_name'], $storedPath)) {
            return "Échec de l'enregistrement du fichier « {$originalName} ».";
        }

        $relativePath = 'storage/uploads/tasklists/' . $tasklistId . '/' . $storedName;
        $mimeType = mime_content_type($storedPath) ?: ($upload['type'] ?? 'application/octet-stream');
        $fileSize = (int)filesize($storedPath);

        $existing = self::findAnswer($tasklistId, $questionId);
        $answerId = $existing['id'] ?? Helper::uuid();

        if (!$existing) {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO tasklist_answers (id, question_id, tasklist_id, text_value) VALUES (?, ?, ?, NULL)"
            );
            $stmt->bind_param('sss', $answerId, $questionId, $tasklistId);
            $stmt->execute();
        }

        $fileId = Helper::uuid();
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO tasklist_answer_files (id, answer_id, original_name, stored_path, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssi', $fileId, $answerId, $originalName, $relativePath, $mimeType, $fileSize);
        $stmt->execute();

        return null;
    }

    private static function findAnswer(string $tasklistId, string $questionId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM tasklist_answers WHERE tasklist_id = ? AND question_id = ? LIMIT 1"
        );
        $stmt->bind_param('ss', $tasklistId, $questionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    private static function uploadDirectory(string $tasklistId): string {
        return dirname(__DIR__, 2) . '/storage/uploads/tasklists/' . $tasklistId;
    }

    public static function parseQuestionsFromPost(array $post): array {
        $raw = $post['questions'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $questions = [];
        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim($item['label'] ?? '');
            $type = trim($item['type'] ?? '');
            if ($label === '' || !isset(self::TYPES[$type])) {
                continue;
            }

            $options = [];
            if (isset($item['options']) && is_array($item['options'])) {
                foreach ($item['options'] as $option) {
                    $option = trim((string)$option);
                    if ($option !== '') {
                        $options[] = $option;
                    }
                }
            }

            if (in_array($type, ['SINGLE_CHOICE', 'MULTIPLE_CHOICE'], true) && count($options) < 2) {
                continue;
            }

            $questions[] = [
                'label' => $label,
                'type' => $type,
                'required' => !empty($item['required']),
                'sort_order' => (int)$index,
                'options' => $options,
            ];
        }

        return $questions;
    }
}
