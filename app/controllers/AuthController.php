<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Email.php';
require_once dirname(__DIR__) . '/models/User.php';

class AuthController {
    public function login(): void {
        Session::start();
        if (Auth::check()) {
            $user = Auth::user();
            $this->redirectByRole($user['role']);
        }

        $error = null;
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($token)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Veuillez remplir tous les champs.';
            } else {
                $user = User::findByEmail($email);
                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['status'] === 'SUSPENDED') {
                        $error = 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.';
                    } else if ($user['status'] === 'PENDING') {
                        $error = 'Votre compte est en attente d\'activation. Veuillez utiliser le lien d\'invitation reçu par email.';
                    } else {
                        // Success
                        Session::set('user_id', $user['id']);
                        Session::set('user', $user);
                        $this->redirectByRole($user['role']);
                    }
                } else {
                    $error = 'Identifiants incorrects.';
                }
            }
        }

        include dirname(__DIR__) . '/views/auth/login.php';
    }

    public function logout(): void {
        Session::destroy();
        Helper::redirect('/login');
    }

    public function resetRequest(): void {
        Session::start();
        if (Auth::check()) {
            $user = Auth::user();
            $this->redirectByRole($user['role']);
        }

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($token)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $error = 'Veuillez saisir votre adresse email.';
            } else if (!Validator::email($email)) {
                $error = 'Adresse email invalide.';
            } else {
                $user = User::findByEmail($email);
                if ($user) {
                    $tokenValue = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    User::update($user['id'], [
                        'invite_token' => $tokenValue,
                        'invite_expiry' => $expiry
                    ]);

                    $resetLink = Helper::appUrl('/reset-password/confirm?token=' . urlencode($tokenValue));
                    $subject = "Réinitialisation de votre mot de passe - e-Media Support";
                    $body = "
                        <h2>Réinitialisation de mot de passe</h2>
                        <p>Bonjour,</p>
                        <p>Vous avez demandé la réinitialisation de votre mot de passe sur e-Media Support.</p>
                        <p>Veuillez cliquer sur le lien ci-dessous pour définir un nouveau mot de passe (valable 1 heure) :</p>
                        <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
                        <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.</p>
                    ";

                    Email::send($email, $subject, $body);
                }
                // Always show success to prevent user enumeration
                $success = 'Si l\'adresse email existe dans notre système, un lien de réinitialisation vous a été envoyé.';
            }
        }

        include dirname(__DIR__) . '/views/auth/reset-password.php';
    }

    public function resetConfirm(): void {
        Session::start();
        if (Auth::check()) {
            $user = Auth::user();
            $this->redirectByRole($user['role']);
        }

        $token = $_GET['token'] ?? $_POST['token'] ?? null;
        if (!$token) {
            Helper::redirect('/login');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE invite_token = ? AND invite_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = 'Le lien de réinitialisation est invalide ou a expiré.';
            include dirname(__DIR__) . '/views/auth/reset-password-confirm.php';
            return;
        }

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';

            if (empty($password) || empty($confirm)) {
                $error = 'Veuillez remplir tous les champs.';
            } else if ($password !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else if (!Validator::password($password)) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                User::update($user['id'], [
                    'password_hash' => $hash,
                    'invite_token' => null,
                    'invite_expiry' => null,
                    'status' => 'ACTIVE',
                    'profile_complete' => 1
                ]);
                $success = 'Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter.';
            }
        }

        include dirname(__DIR__) . '/views/auth/reset-password-confirm.php';
    }

    private function redirectByRole(string $role): void {
        if ($role === 'ADMIN') {
            Helper::redirect('/admin/dashboard');
        } else if ($role === 'DEV') {
            Helper::redirect('/developer/dashboard');
        } else if ($role === 'CLIENT') {
            Helper::redirect('/client/dashboard');
        } else {
            Helper::redirect('/login');
        }
    }
}
