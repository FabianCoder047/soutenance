<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Email.php';
require_once dirname(__DIR__) . '/core/Audit.php';
require_once dirname(__DIR__) . '/models/Wishlist.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/User.php';

class WishlistController {
    public function indexAdmin(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        $filters = [
            'status' => $_GET['status'] ?? '',
            'project_id' => $_GET['project_id'] ?? ''
        ];

        $total = Wishlist::countAll($filters);
        $pagination = Helper::paginationFromRequest($_GET, $total);
        $filters['limit'] = $pagination['per_page'];
        $filters['offset'] = $pagination['offset'];

        $wishlists = Wishlist::getAll($filters);
        $projects = Project::getAll(['status' => 'ACTIVE']);

        include dirname(__DIR__) . '/views/admin/wishlists.php';
    }

    public function updateStatus(): void {
        Auth::require('ADMIN');
        $admin = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('/admin/wishlists');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $wishlistId = $_POST['wishlist_id'] ?? '';
        $status = $_POST['status'] ?? '';

        if (empty($wishlistId) || !in_array($status, ['PENDING', 'ACKNOWLEDGED', 'DONE'], true)) {
            Session::set('error', "Requête de mise à jour invalide.");
            Helper::redirect('/admin/wishlists');
        }

        $wishlist = Wishlist::findById($wishlistId);
        if (!$wishlist) {
            Session::set('error', "Demande introuvable.");
            Helper::redirect('/admin/wishlists');
        }

        Wishlist::updateStatus($wishlistId, $status);

        Session::set('success', "Le statut de la demande a été mis à jour à \"{$status}\".");
        Helper::redirect('/admin/wishlists');
    }

    public function indexClient(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        $filters = ['client_id' => $user['id']];
        $total = Wishlist::countAll($filters);
        $pagination = Helper::paginationFromRequest($_GET, $total);
        $filters['limit'] = $pagination['per_page'];
        $filters['offset'] = $pagination['offset'];

        $wishlists = Wishlist::getAll($filters);

        include dirname(__DIR__) . '/views/client/wishlists.php';
    }

    public function create(): void {
        Auth::require('CLIENT');
        $user = Auth::user();

        $error = null;
        $content = '';
        $projectId = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $content = trim($_POST['content'] ?? '');
            $projectId = $_POST['project_id'] ?? '';

            if (empty($content)) {
                $error = "Le contenu de votre liste d'attente est obligatoire.";
            } else {
                if (!empty($projectId)) {
                    $project = Project::findById($projectId);
                    if (!$project || $project['client_id'] !== $user['id']) {
                        $error = "Le projet sélectionné est invalide.";
                    }
                }

                if (!$error) {
                    $wishlistId = Helper::uuid();
                    Wishlist::create([
                        'id' => $wishlistId,
                        'content' => $content,
                        'status' => 'PENDING',
                        'client_id' => $user['id'],
                        'project_id' => $projectId ?: null
                    ]);

                    // Audit Log
                    $preview = mb_strimwidth($content, 0, 50, '...');
                    Audit::logAction('WISHLIST_CREATED', 'Wishlist', $wishlistId, $user['id'], [
                        'content_preview' => $preview
                    ]);

                    // Notification email aux administrateurs
                    $admins = User::getAdmins();
                    foreach ($admins as $admin) {
                        if (!empty($admin['email'])) {
                            Email::notification(
                                $admin['email'],
                                'Nouvelle demande client – e-Media Support',
                                "Un client de la plateforme a soumis une nouvelle demande. Connectez-vous à votre espace pour la consulter."
                            );
                        }
                    }

                    Session::set('success', "Votre liste d'attentes a été envoyée avec succès.");
                    Helper::redirect('/client/wishlists');
                }
            }
        }

        $projects = Project::getAll(['client_id' => $user['id'], 'status' => 'ACTIVE']);
        include dirname(__DIR__) . '/views/client/wishlist-new.php';
    }
}
