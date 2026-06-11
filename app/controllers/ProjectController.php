<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Audit.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/User.php';

class ProjectController {
    public function indexAdmin(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['client_id'])) {
            $filters['client_id'] = $_GET['client_id'];
        }

        $projects = Project::getAll($filters);
        $clients = User::getClients();

        include dirname(__DIR__) . '/views/admin/projects.php';
    }

    public function create(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        $error = null;
        $name = '';
        $description = '';
        $clientId = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $clientId = $_POST['client_id'] ?? '';

            if (empty($name) || empty($clientId)) {
                $error = "Le nom du projet et le client sont obligatoires.";
            } else {
                $client = User::findById($clientId);
                if (!$client || $client['role'] !== 'CLIENT') {
                    $error = "Le client sélectionné est invalide.";
                } else {
                    $projectId = Helper::uuid();
                    Project::create([
                        'id' => $projectId,
                        'name' => $name,
                        'description' => $description,
                        'status' => 'ACTIVE',
                        'client_id' => $clientId,
                        'created_by_id' => $user['id']
                    ]);

                    // Audit Log
                    Audit::logAction('PROJECT_CREATED', 'Project', $projectId, $user['id'], [
                        'project_name' => $name,
                        'client_id' => $clientId,
                        'client_name' => $client['company_name'] ?? $client['email']
                    ]);

                    Session::set('success', "Le projet \"{$name}\" a été créé avec succès.");
                    
                    if ($user['role'] === 'ADMIN') {
                        Helper::redirect('/admin/projects');
                    } else {
                        Helper::redirect('/developer/my-projects');
                    }
                }
            }
        }

        $clients = User::getClients();
        
        // Render project creation form
        include dirname(__DIR__) . '/views/admin/project-new.php';
    }

    public function edit(): void {
        Auth::require('ADMIN', 'DEV');
        $user = Auth::user();

        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            if ($user['role'] === 'ADMIN') {
                Helper::redirect('/admin/projects');
            } else {
                Helper::redirect('/developer/my-projects');
            }
        }

        $project = Project::findById($id);
        if (!$project) {
            Session::set('error', "Projet introuvable.");
            if ($user['role'] === 'ADMIN') {
                Helper::redirect('/admin/projects');
            } else {
                Helper::redirect('/developer/my-projects');
            }
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $clientId = $_POST['client_id'] ?? '';
            $status = $_POST['status'] ?? 'ACTIVE';

            if (empty($name) || empty($clientId)) {
                $error = "Le nom du projet et le client sont obligatoires.";
            } else {
                $client = User::findById($clientId);
                if (!$client || $client['role'] !== 'CLIENT') {
                    $error = "Le client sélectionné est invalide.";
                } else {
                    Project::update($id, [
                        'name' => $name,
                        'description' => $description,
                        'client_id' => $clientId,
                        'status' => $status
                    ]);

                    Session::set('success', "Le projet \"{$name}\" a été mis à jour.");
                    
                    if ($user['role'] === 'ADMIN') {
                        Helper::redirect('/admin/projects');
                    } else {
                        Helper::redirect('/developer/my-projects');
                    }
                }
            }
        }

        $clients = User::getClients();
        include dirname(__DIR__) . '/views/admin/project-edit.php';
    }

    public function archive(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('/admin/projects');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $id = $_POST['id'] ?? '';
        $project = Project::findById($id);

        if (!$project) {
            Session::set('error', "Projet introuvable.");
            Helper::redirect('/admin/projects');
        }

        Project::archive($id);

        // Audit Log
        Audit::logAction('PROJECT_ARCHIVED', 'Project', $id, $user['id'], [
            'project_name' => $project['name']
        ]);

        Session::set('success', "Le projet \"{$project['name']}\" a été archivé.");
        Helper::redirect('/admin/projects');
    }

    public function indexDev(): void {
        Auth::require('DEV');
        $user = Auth::user();

        // Projects created by this dev or projects active in general
        // The requirements say:
        // - "my-projects: Projets créés par ce dev"
        // - "projects: Vue lecture de tous les projets actifs"
        // Wait, let's see which route we are on:
        // /developer/my-projects maps to ProjectController@indexDev
        // Let's implement indexDev to fetch projects created by this dev,
        // and we can have another method or route for "projects" page?
        // Wait, yes, in 5.3:
        // `/developer/my-projects` -> `ProjectController@indexDev`
        // But what about the page `/developer/projects` mentioned in 4.2?
        // Let's look at 5.3: there is no route `/developer/projects` in the routing table (section 5.3).
        // Let's add `/developer/projects` (GET) -> `ProjectController@indexDevAllActive` or handle it inside `indexDev`.
        // Let's support both or just put them both in `indexDev` by passing a GET filter, or create a route:
        // Let's add `/developer/projects` to map to `ProjectController@indexDevAll` or similar. This is very clean and aligns with the pages defined in 4.2!
        
        $myProjectsOnly = true;
        $projects = Project::getAll(['created_by_id' => $user['id']]);

        include dirname(__DIR__) . '/views/developer/my-projects.php';
    }

    public function indexDevAll(): void {
        Auth::require('DEV');
        $user = Auth::user();

        // All active projects (read-only view)
        $projects = Project::getAll(['status' => 'ACTIVE']);

        include dirname(__DIR__) . '/views/developer/projects.php';
    }

    public function indexClient(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        // Client projects
        $projects = Project::getAll(['client_id' => $user['id']]);

        include dirname(__DIR__) . '/views/client/projects.php';
    }
}
