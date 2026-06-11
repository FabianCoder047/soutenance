<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Audit.php';
require_once dirname(__DIR__) . '/models/Tasklist.php';
require_once dirname(__DIR__) . '/models/TasklistQuestion.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/User.php';

class TasklistController {
    public function indexAdmin(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        $filters = [
            'project_id' => $_GET['project_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'assigned_to_id' => $_GET['assigned_to_id'] ?? ''
        ];

        $tasklists = Tasklist::getAll($filters);
        $projects = Project::getAll(['status' => 'ACTIVE']);
        $devs = User::getDevelopers();
        $answersByTasklist = $this->loadAnswersForTasklists($tasklists);

        include dirname(__DIR__) . '/views/admin/tasklists.php';
    }

    public function create(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        $error = null;
        $title = '';
        $description = '';
        $projectId = $_GET['project_id'] ?? '';
        $assignedToId = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $projectId = $_POST['project_id'] ?? '';
            $questions = TasklistQuestion::parseQuestionsFromPost($_POST);

            if ($user['role'] === 'DEV') {
                $assignedToId = $user['id'];
            } else {
                $assignedToId = $_POST['assigned_to_id'] ?? '';
                if (empty($assignedToId)) {
                    $assignedToId = $user['id'];
                }
            }

            if (empty($title) || empty($projectId)) {
                $error = "Le titre et le projet sont obligatoires.";
            } elseif (empty($questions)) {
                $error = "Ajoutez au moins une question à la tasklist.";
            } else {
                $project = Project::findById($projectId);
                if (!$project || $project['status'] === 'ARCHIVED') {
                    $error = "Le projet sélectionné est invalide ou archivé.";
                } else {
                    $tasklistId = Helper::uuid();
                    Tasklist::create([
                        'id' => $tasklistId,
                        'title' => $title,
                        'description' => $description,
                        'status' => 'DRAFT',
                        'project_id' => $projectId,
                        'created_by_id' => $user['id'],
                        'assigned_to_id' => $assignedToId
                    ]);

                    TasklistQuestion::createBatch($tasklistId, $questions);

                    Audit::logAction('TASKLIST_CREATED', 'Tasklist', $tasklistId, $user['id'], [
                        'title' => $title,
                        'project_id' => $projectId,
                        'assigned_to_id' => $assignedToId,
                        'questions_count' => count($questions),
                    ]);

                    Session::set('success', "La tasklist \"{$title}\" a été enregistrée en brouillon. Envoyez-la au client lorsqu'elle est prête.");

                    Helper::redirect('/tasklists/show?id=' . urlencode($tasklistId));
                }
            }
        }

        $projects = Project::getAll(['status' => 'ACTIVE']);
        $devs = User::getDevelopers();

        $isEdit = false;
        $tasklistId = '';
        $existingQuestions = [];

        include dirname(__DIR__) . '/views/admin/tasklist-new.php';
    }

    public function edit(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        $tasklistId = $_GET['id'] ?? $_POST['tasklist_id'] ?? '';
        if ($tasklistId === '') {
            Session::set('error', 'Tasklist introuvable.');
            Helper::redirect($this->tasklistListUrl($user));
        }

        $tasklist = Tasklist::findById($tasklistId);
        if (!$tasklist || !Tasklist::canEdit($user, $tasklist)) {
            Session::set('error', 'Cette tasklist ne peut pas être modifiée.');
            Helper::redirect($this->tasklistListUrl($user));
        }

        $error = null;
        $title = $tasklist['title'];
        $description = $tasklist['description'] ?? '';
        $projectId = $tasklist['project_id'];
        $assignedToId = $tasklist['assigned_to_id'] ?? '';
        $existingQuestions = TasklistQuestion::getByTasklistId($tasklistId);
        $isEdit = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $projectId = $_POST['project_id'] ?? $projectId;
            $questions = TasklistQuestion::parseQuestionsFromPost($_POST);

            if ($user['role'] === 'DEV') {
                $assignedToId = $user['id'];
            } else {
                $assignedToId = $_POST['assigned_to_id'] ?? $assignedToId;
                if ($assignedToId === '') {
                    $assignedToId = $user['id'];
                }
            }

            if ($title === '' || $projectId === '') {
                $error = 'Le titre et le projet sont obligatoires.';
            } elseif (empty($questions)) {
                $error = 'Ajoutez au moins une question à la tasklist.';
            } else {
                $project = Project::findById($projectId);
                if (!$project || $project['status'] === 'ARCHIVED') {
                    $error = 'Le projet sélectionné est invalide ou archivé.';
                } else {
                    Tasklist::update($tasklistId, [
                        'title' => $title,
                        'description' => $description,
                        'project_id' => $projectId,
                        'assigned_to_id' => $assignedToId,
                    ]);
                    TasklistQuestion::replaceBatch($tasklistId, $questions);

                    Audit::logAction('TASKLIST_UPDATED', 'Tasklist', $tasklistId, $user['id'], [
                        'title' => $title,
                        'project_id' => $projectId,
                        'questions_count' => count($questions),
                    ]);

                    Session::set('success', "La tasklist \"{$title}\" a été mise à jour.");
                    Helper::redirect('/tasklists/show?id=' . urlencode($tasklistId));
                }
            }

            $existingQuestions = TasklistQuestion::getByTasklistId($tasklistId);
        }

        $projects = Project::getAll(['status' => 'ACTIVE']);
        $devs = User::getDevelopers();

        include dirname(__DIR__) . '/views/admin/tasklist-new.php';
    }

    public function assign(): void {
        Auth::require('ADMIN');
        $admin = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('/admin/tasklists');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $tasklistId = $_POST['tasklist_id'] ?? '';
        $assignedToId = $_POST['assigned_to_id'] ?? '';

        if (empty($tasklistId) || empty($assignedToId)) {
            Session::set('error', "Données d'assignation incomplètes.");
            Helper::redirect('/admin/tasklists');
        }

        $tasklist = Tasklist::findById($tasklistId);
        if (!$tasklist) {
            Session::set('error', "Tasklist introuvable.");
            Helper::redirect('/admin/tasklists');
        }

        $dev = User::findById($assignedToId);
        if (!$dev || !in_array($dev['role'], ['DEV', 'ADMIN'], true)) {
            Session::set('error', "Développeur sélectionné invalide.");
            Helper::redirect('/admin/tasklists');
        }

        $prevAssignee = $tasklist['assigned_to_id'];
        Tasklist::assign($tasklistId, $assignedToId);

        Audit::logAction('TASKLIST_ASSIGNED', 'Tasklist', $tasklistId, $admin['id'], [
            'previous_assignee' => $prevAssignee,
            'new_assignee' => $assignedToId
        ]);

        Session::set('success', "La tasklist a été réassignée à " . ($dev['first_name'] ?? '') . " " . ($dev['last_name'] ?? '') . ".");
        Helper::redirect('/admin/tasklists');
    }

    public function treat(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($user['role'] === 'ADMIN') {
                Helper::redirect('/admin/tasklists');
            } else {
                Helper::redirect('/developer/my-tasklists');
            }
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $tasklistId = $_POST['tasklist_id'] ?? '';
        if (empty($tasklistId)) {
            Session::set('error', "ID de tasklist invalide.");
            Helper::redirect($user['role'] === 'ADMIN' ? '/admin/tasklists' : '/developer/my-tasklists');
        }

        $tasklist = Tasklist::findById($tasklistId);
        if (!$tasklist) {
            Session::set('error', "Tasklist introuvable.");
            Helper::redirect($user['role'] === 'ADMIN' ? '/admin/tasklists' : '/developer/my-tasklists');
        }

        if ($user['role'] === 'DEV' && $tasklist['assigned_to_id'] !== $user['id']) {
            Session::set('error', "Vous n'êtes pas assigné à cette tasklist.");
            Helper::redirect('/developer/my-tasklists');
        }

        Tasklist::treat($tasklistId);

        Audit::logAction('TASKLIST_TREATED', 'Tasklist', $tasklistId, $user['id'], [
            'treated_at' => date('Y-m-d H:i:s')
        ]);

        Session::set('success', "La tasklist \"{$tasklist['title']}\" a été marquée comme traitée.");

        if ($user['role'] === 'ADMIN') {
            Helper::redirect('/admin/tasklists');
        } else {
            Helper::redirect('/developer/my-tasklists');
        }
    }

    public function indexDev(): void {
        Auth::require('DEV');
        $user = Auth::user();

        $filters = [
            'dev_access_id' => $user['id'],
            'status' => $_GET['status'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
        ];

        $tasklists = Tasklist::getAll($filters);
        $answersByTasklist = $this->loadAnswersForTasklists($tasklists);
        $filteredProject = !empty($filters['project_id'])
            ? Project::findById($filters['project_id'])
            : null;

        include dirname(__DIR__) . '/views/developer/my-tasklists.php';
    }

    public function indexClient(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        $filters = [
            'client_id' => $user['id'],
            'status' => $_GET['status'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'exclude_draft' => true,
        ];

        $tasklists = Tasklist::getAll($filters);
        $answersByTasklist = $this->loadAnswersForTasklists($tasklists);
        $filteredProject = !empty($filters['project_id'])
            ? Project::findById($filters['project_id'])
            : null;

        include dirname(__DIR__) . '/views/client/tasklists.php';
    }

    public function show(): void {
        Auth::require('ADMIN', 'DEV', 'CLIENT');
        $user = Auth::user();

        $tasklistId = $_GET['id'] ?? '';
        if ($tasklistId === '') {
            Session::set('error', 'Tasklist introuvable.');
            Helper::redirect($this->tasklistListUrl($user));
        }

        $tasklist = Tasklist::findById($tasklistId);
        if (!$tasklist || !Tasklist::canView($user, $tasklist)) {
            Session::set('error', "Vous n'êtes pas autorisé à consulter cette tasklist.");
            Helper::redirect($this->tasklistListUrl($user));
        }

        $questions = TasklistQuestion::getByTasklistId($tasklistId);
        $answers = !empty($tasklist['client_filled'])
            ? TasklistQuestion::getAnswersForTasklist($tasklistId)
            : [];

        $backUrl = $this->tasklistListUrl($user);
        $canEdit = Tasklist::canEdit($user, $tasklist);
        $canPublish = $canEdit;
        $canDelete = Tasklist::canDelete($user, $tasklist);
        $canRespond = Tasklist::canClientRespond($user, $tasklist);
        $canTreat = in_array($user['role'], ['ADMIN', 'DEV'], true)
            && $tasklist['status'] !== 'DONE'
            && !Tasklist::isDraft($tasklist)
            && ($user['role'] === 'ADMIN' || $tasklist['assigned_to_id'] === $user['id']);

        $layout = match ($user['role']) {
            'ADMIN' => 'admin',
            'DEV' => 'developer',
            default => 'client',
        };

        include dirname(__DIR__) . '/views/tasklists/show.php';
    }

    public function delete(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect($this->tasklistListUrl($user));
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $tasklistId = $_POST['tasklist_id'] ?? '';
        $tasklist = $tasklistId !== '' ? Tasklist::findById($tasklistId) : null;

        if (!$tasklist || !Tasklist::canDelete($user, $tasklist)) {
            Session::set('error', 'Vous ne pouvez pas supprimer cette tasklist.');
            Helper::redirect($this->tasklistListUrl($user));
        }

        $title = $tasklist['title'];
        Tasklist::delete($tasklistId);

        Audit::logAction('TASKLIST_DELETED', 'Tasklist', $tasklistId, $user['id'], [
            'title' => $title,
            'status' => $tasklist['status'],
        ]);

        Session::set('success', "La tasklist \"{$title}\" a été supprimée.");
        Helper::redirect($this->tasklistListUrl($user));
    }

    public function publish(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect($this->tasklistListUrl($user));
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $tasklistId = $_POST['tasklist_id'] ?? '';
        $tasklist = $tasklistId !== '' ? Tasklist::findById($tasklistId) : null;

        if (!$tasklist || $tasklist['status'] !== 'DRAFT') {
            Session::set('error', 'Cette tasklist ne peut pas être envoyée.');
            Helper::redirect($this->tasklistListUrl($user));
        }

        if ($user['role'] === 'DEV'
            && $tasklist['assigned_to_id'] !== $user['id']
            && $tasklist['created_by_id'] !== $user['id']) {
            Session::set('error', "Vous n'êtes pas autorisé à envoyer cette tasklist.");
            Helper::redirect('/developer/my-tasklists');
        }

        if (TasklistQuestion::countByTasklistId($tasklistId) === 0) {
            Session::set('error', 'Ajoutez au moins une question avant d\'envoyer la tasklist au client.');
            Helper::redirect('/tasklists/show?id=' . urlencode($tasklistId));
        }

        Tasklist::publish($tasklistId);

        Audit::logAction('TASKLIST_PUBLISHED', 'Tasklist', $tasklistId, $user['id'], [
            'title' => $tasklist['title'],
        ]);

        Session::set('success', 'La tasklist a été envoyée au client. Il peut maintenant la consulter et y répondre.');
        Helper::redirect('/tasklists/show?id=' . urlencode($tasklistId));
    }

    public function clientFillForm(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        $tasklistId = $_GET['id'] ?? '';
        if ($tasklistId === '') {
            Session::set('error', "Tasklist introuvable.");
            Helper::redirect('/client/tasklists');
        }

        $tasklist = Tasklist::findById($tasklistId);
        if (!$tasklist || !Tasklist::canView($user, $tasklist)) {
            Session::set('error', "Vous n'êtes pas autorisé à accéder à cette tasklist.");
            Helper::redirect('/client/tasklists');
        }

        if ($tasklist['status'] === 'DONE') {
            Session::set('error', "Cette tasklist a déjà été traitée.");
            Helper::redirect('/client/tasklists');
        }

        if (!Tasklist::canClientRespond($user, $tasklist)) {
            Session::set('success', 'Vous avez déjà envoyé votre réponse pour cette tasklist.');
            Helper::redirect('/tasklists/show?id=' . urlencode($tasklistId));
        }

        $questions = TasklistQuestion::getByTasklistId($tasklistId);
        $existingAnswers = TasklistQuestion::getAnswersForTasklist($tasklistId);
        $answersByQuestion = [];
        foreach ($existingAnswers as $answer) {
            $answersByQuestion[$answer['question_id']] = $answer;
        }

        include dirname(__DIR__) . '/views/client/tasklist-fill.php';
    }

    public function clientFill(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('/client/tasklists');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $tasklistId = $_POST['tasklist_id'] ?? '';
        if ($tasklistId === '') {
            Session::set('error', "Tasklist introuvable.");
            Helper::redirect('/client/tasklists');
        }

        $tasklist = Tasklist::findById($tasklistId);
        if (!$tasklist) {
            Session::set('error', "Tasklist introuvable.");
            Helper::redirect('/client/tasklists');
        }

        if (!Tasklist::canView($user, $tasklist)) {
            Session::set('error', "Vous n'êtes pas autorisé à modifier cette tasklist.");
            Helper::redirect('/client/tasklists');
        }

        if ($tasklist['status'] === 'DONE') {
            Session::set('error', "Cette tasklist a déjà été traitée.");
            Helper::redirect('/client/tasklists');
        }

        if (Tasklist::isDraft($tasklist)) {
            Session::set('error', "Cette tasklist n'est pas encore disponible.");
            Helper::redirect('/client/tasklists');
        }

        if (!Tasklist::canClientRespond($user, $tasklist)) {
            Session::set('error', 'Vous avez déjà envoyé votre réponse pour cette tasklist.');
            Helper::redirect('/tasklists/show?id=' . urlencode($tasklistId));
        }

        $questions = TasklistQuestion::getByTasklistId($tasklistId);
        if (empty($questions)) {
            Session::set('error', "Cette tasklist ne contient aucune question.");
            Helper::redirect('/client/tasklists');
        }

        $postAnswers = $_POST['answers'] ?? [];
        $files = $_FILES['files'] ?? [];
        $normalizedFiles = $this->normalizeUploadedFiles($files);

        $errors = TasklistQuestion::saveAnswers($tasklistId, $questions, $postAnswers, $normalizedFiles);
        if (!empty($errors)) {
            Session::set('error', implode(' ', $errors));
            Helper::redirect('/client/tasklists/fill?id=' . urlencode($tasklistId));
        }

        Tasklist::clientFill($tasklistId, null);

        Audit::logAction('TASKLIST_CLIENT_FILLED', 'Tasklist', $tasklistId, $user['id'], [
            'client_id' => $user['id'],
            'questions_answered' => count($questions),
        ]);

        Session::set('success', "Votre réponse a été enregistrée avec succès.");
        Helper::redirect('/client/tasklists');
    }

    public function downloadFile(): void {
        Auth::require('ADMIN', 'DEV', 'CLIENT');
        $user = Auth::user();

        $fileId = $_GET['id'] ?? '';
        if ($fileId === '') {
            http_response_code(404);
            exit('Fichier introuvable');
        }

        $file = TasklistQuestion::findFileById($fileId);
        if (!$file) {
            http_response_code(404);
            exit('Fichier introuvable');
        }

        $tasklist = Tasklist::findById($file['tasklist_id']);
        if (!$tasklist) {
            http_response_code(404);
            exit('Fichier introuvable');
        }

        if (!$this->canAccessTasklistFile($user, $tasklist)) {
            http_response_code(403);
            exit('Accès refusé');
        }

        $absolutePath = dirname(__DIR__, 2) . '/' . ltrim($file['stored_path'], '/');
        if (!is_file($absolutePath)) {
            http_response_code(404);
            exit('Fichier introuvable');
        }

        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
        header('Content-Length: ' . (string)filesize($absolutePath));
        readfile($absolutePath);
        exit;
    }

    private function loadAnswersForTasklists(array $tasklists): array {
        $answersByTasklist = [];
        foreach ($tasklists as $tasklist) {
            if (!empty($tasklist['client_filled'])) {
                $answersByTasklist[$tasklist['id']] = TasklistQuestion::getAnswersForTasklist($tasklist['id']);
            }
        }
        return $answersByTasklist;
    }

    private function normalizeUploadedFiles(array $files): array {
        $normalized = [];

        if (!isset($files['name']) || !is_array($files['name'])) {
            return $normalized;
        }

        foreach ($files['name'] as $questionId => $name) {
            $normalized[$questionId] = [
                'name' => $name,
                'type' => $files['type'][$questionId] ?? '',
                'tmp_name' => $files['tmp_name'][$questionId] ?? '',
                'error' => $files['error'][$questionId] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$questionId] ?? 0,
            ];
        }

        return $normalized;
    }

    private function canAccessTasklistFile(array $user, array $tasklist): bool {
        if ($user['role'] === 'ADMIN') {
            return true;
        }
        if ($user['role'] === 'CLIENT') {
            return ($tasklist['project_client_id'] ?? '') === $user['id'];
        }
        if ($user['role'] === 'DEV') {
            return ($tasklist['assigned_to_id'] ?? '') === $user['id']
                || ($tasklist['created_by_id'] ?? '') === $user['id'];
        }
        return false;
    }

    private function tasklistListUrl(array $user): string {
        return match ($user['role']) {
            'ADMIN' => '/admin/tasklists',
            'DEV' => '/developer/my-tasklists',
            default => '/client/tasklists',
        };
    }
}
