<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/Tasklist.php';

class DeveloperController {
    public function dashboard(): void {
        Auth::require('DEV');
        $user = Auth::user();
        $db = Database::getInstance();

        // KPIs
        $projectsCount = 0;
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM projects WHERE created_by_id = ?");
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $projectsCount = (int)$stmt->get_result()->fetch_assoc()['c'];

        $assignedCount = 0;
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM tasklists WHERE assigned_to_id = ? AND status IN ('PENDING', 'IN_PROGRESS', 'CLIENT_FILLED')");
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $assignedCount = (int)$stmt->get_result()->fetch_assoc()['c'];

        $treatedCount = 0;
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM tasklists WHERE assigned_to_id = ? AND status = 'DONE'");
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $treatedCount = (int)$stmt->get_result()->fetch_assoc()['c'];

        // Get 5 recent tasklists assigned to this developer
        $recentTasklists = [];
        $stmt = $db->prepare(
            "SELECT t.*, p.name AS project_name, c.company_name AS client_company 
             FROM tasklists t
             JOIN projects p ON t.project_id = p.id
             JOIN users c ON p.client_id = c.id
             WHERE t.assigned_to_id = ? 
             ORDER BY t.created_at DESC 
             LIMIT 5"
        );
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $recentTasklists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        include dirname(__DIR__) . '/views/developer/dashboard.php';
    }

    public function profile(): void {
        Auth::require('DEV');
        $user = Auth::user();

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';

            if (empty($firstName) || empty($lastName)) {
                $error = 'Le prénom et le nom sont obligatoires.';
            } else {
                $updateData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ];

                if (!empty($password)) {
                    if ($password !== $confirm) {
                        $error = 'Les mots de passe ne correspondent pas.';
                    } else if (!Validator::password($password)) {
                        $error = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.';
                    } else {
                        $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    }
                }

                if (!$error) {
                    if (User::update($user['id'], $updateData)) {
                        $updatedUser = User::findById($user['id']);
                        Session::set('user', $updatedUser);
                        $user = $updatedUser;
                        $success = 'Profil mis à jour avec succès.';
                    } else {
                        $error = 'Une erreur est survenue lors de la mise à jour.';
                    }
                }
            }
        }

        include dirname(__DIR__) . '/views/developer/profile.php';
    }
}
