<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/Tasklist.php';
require_once dirname(__DIR__) . '/models/Wishlist.php';

class ClientController {
    public function dashboard(): void {
        Auth::require('CLIENT');
        $user = Auth::user();
        $db = Database::getInstance();

        // KPIs
        $projectsCount = 0;
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM projects WHERE client_id = ? AND status = 'ACTIVE'");
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $projectsCount = (int)$stmt->get_result()->fetch_assoc()['c'];

        $pendingCount = 0;
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS c 
             FROM tasklists t 
             JOIN projects p ON t.project_id = p.id 
             WHERE p.client_id = ? AND t.client_filled = 0 AND t.status NOT IN ('DRAFT', 'DONE')"
        );
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $pendingCount = (int)$stmt->get_result()->fetch_assoc()['c'];

        $pendingWishlistsCount = 0;
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS c FROM wishlists WHERE client_id = ? AND status = 'PENDING'"
        );
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $pendingWishlistsCount = (int)$stmt->get_result()->fetch_assoc()['c'];

        // Get 5 recent tasklists that need action
        $recentTasklists = [];
        $stmt = $db->prepare(
            "SELECT t.*, p.name AS project_name 
             FROM tasklists t
             JOIN projects p ON t.project_id = p.id
             WHERE p.client_id = ? AND t.client_filled = 0 AND t.status NOT IN ('DRAFT', 'DONE')
             ORDER BY t.created_at DESC 
             LIMIT 5"
        );
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $recentTasklists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        include dirname(__DIR__) . '/views/client/dashboard.php';
    }

    public function profile(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $companyName = trim($_POST['company_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';

            if (empty($companyName) || empty($address)) {
                $error = 'Le nom de l\'entreprise et l\'adresse sont obligatoires.';
            } else {
                $updateData = [
                    'company_name' => $companyName,
                    'address' => $address
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

        include dirname(__DIR__) . '/views/client/profile.php';
    }
}
