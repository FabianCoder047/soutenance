<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Audit.php';
require_once dirname(__DIR__) . '/core/Email.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Project.php';
require_once dirname(__DIR__) . '/models/Tasklist.php';
require_once dirname(__DIR__) . '/models/Wishlist.php';

class AdminController {
    public function dashboard(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        $db = Database::getInstance();

        // KPIs
        $projectsCount = 0;
        if ($res = $db->query("SELECT COUNT(*) AS c FROM projects WHERE status = 'ACTIVE'")) {
            $projectsCount = (int)$res->fetch_assoc()['c'];
        }

        $tasklistsCount = 0;
        if ($res = $db->query("SELECT COUNT(*) AS c FROM tasklists WHERE status IN ('PENDING', 'IN_PROGRESS', 'CLIENT_FILLED')")) {
            $tasklistsCount = (int)$res->fetch_assoc()['c'];
        }

        $clientsCount = 0;
        if ($res = $db->query("SELECT COUNT(*) AS c FROM users WHERE role = 'CLIENT' AND status = 'ACTIVE'")) {
            $clientsCount = (int)$res->fetch_assoc()['c'];
        }

        $wishlistsCount = 0;
        if ($res = $db->query("SELECT COUNT(*) AS c FROM wishlists WHERE status = 'PENDING'")) {
            $wishlistsCount = (int)$res->fetch_assoc()['c'];
        }

        // Recent audit logs for dashboard view
        $recentLogs = [];
        if ($res = $db->query("SELECT a.*, u.email FROM audit_logs a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5")) {
            $recentLogs = $res->fetch_all(MYSQLI_ASSOC);
        }

        include dirname(__DIR__) . '/views/admin/dashboard.php';
    }

    public function users(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        $filters = [
            'role' => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $users = User::getAll($filters);

        include dirname(__DIR__) . '/views/admin/users.php';
    }

    public function inviteUser(): void {
        Auth::require('ADMIN');
        $admin = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('/admin/users');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        if (empty($email) || empty($role)) {
            Session::set('error', 'Veuillez saisir un email et choisir un rôle.');
            Helper::redirect('/admin/users');
        }

        if (!Validator::email($email)) {
            Session::set('error', 'Adresse email invalide.');
            Helper::redirect('/admin/users');
        }

        if (!in_array($role, ['ADMIN', 'DEV', 'CLIENT'], true)) {
            Session::set('error', 'Rôle sélectionné invalide.');
            Helper::redirect('/admin/users');
        }

        $existing = User::findByEmail($email);
        if ($existing) {
            Session::set('error', 'Un utilisateur possède déjà cette adresse email.');
            Helper::redirect('/admin/users');
        }

        // Generate invitation token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+72 hours'));
        $userId = Helper::uuid();

        User::create([
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'status' => 'PENDING',
            'invite_token' => $token,
            'invite_expiry' => $expiry,
            'profile_complete' => 0
        ]);

        // Send email
        $inviteLink = Helper::appUrl('/invitation?token=' . urlencode($token));
        $subject = "Invitation à rejoindre e-Media Support";
        $body = "
            <h2>Invitation e-Media Support</h2>
            <p>Bonjour,</p>
            <p>Vous avez été invité à rejoindre e-Media Support en tant que <strong>" . Helper::escape($role) . "</strong>.</p>
            <p>Veuillez cliquer sur le lien ci-dessous pour compléter votre profil et activer votre compte :</p>
            <p><a href=\"{$inviteLink}\">{$inviteLink}</a></p>
            <p>Ce lien d'invitation expirera dans 72 heures.</p>
        ";

        $sent = Email::send($email, $subject, $body);

        // Audit log
        Audit::logAction('USER_INVITED', 'User', $userId, $admin['id'], [
            'email' => $email,
            'role' => $role,
            'email_sent' => $sent,
        ]);

        if ($sent) {
            Session::set('success', 'Invitation créée et email envoyé à ' . $email . ' avec succès.');
        } else {
            Session::set(
                'error',
                'Le compte invitation a été créé, mais l\'envoi de l\'email a échoué. '
                . 'Vérifiez MAIL_USERNAME / MAIL_PASSWORD dans .env et consultez logs/mail.log. '
                . 'Pour un utilisateur en attente, utilisez « Copier lien » sur la liste.'
            );
        }
        Helper::redirect('/admin/users');
    }

    public function updateStatus(): void {
        Auth::require('ADMIN');
        $admin = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('/admin/users');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Session::verifyCsrfToken($csrf)) {
            http_response_code(403);
            exit('CSRF token invalide');
        }

        $userId = $_POST['user_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (empty($userId) || !in_array($status, ['ACTIVE', 'SUSPENDED'], true)) {
            Session::set('error', 'Requête invalide.');
            Helper::redirect('/admin/users');
        }

        if ($userId === $admin['id']) {
            Session::set('error', 'Vous ne pouvez pas suspendre votre propre compte.');
            Helper::redirect('/admin/users');
        }

        $user = User::findById($userId);
        if (!$user) {
            Session::set('error', 'Utilisateur introuvable.');
            Helper::redirect('/admin/users');
        }

        User::update($userId, ['status' => $status]);

        // Audit Log
        if ($status === 'SUSPENDED') {
            Audit::logAction('USER_SUSPENDED', 'User', $userId, $admin['id'], [
                'email' => $user['email'],
                'reason' => $reason ?: 'Aucune raison spécifiée'
            ]);
            Session::set('success', 'Le compte de ' . $user['email'] . ' a été suspendu.');
        } else {
            Audit::logAction('USER_REACTIVATED', 'User', $userId, $admin['id'], [
                'email' => $user['email']
            ]);
            Session::set('success', 'Le compte de ' . $user['email'] . ' a été réactivé.');
        }

        Helper::redirect('/admin/users');
    }

    public function profile(): void {
        Auth::require('ADMIN');
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

        include dirname(__DIR__) . '/views/admin/profile.php';
    }
}
