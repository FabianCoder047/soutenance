<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Email.php';
require_once dirname(__DIR__) . '/core/Audit.php';
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
                        // Crédentiels valides : envoi d'un code de vérification à 6 chiffres (300 s)
                        $otp = (string) random_int(100000, 999999);
                        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
                        $otpExpiry = date('Y-m-d H:i:s', time() + 300);

                        User::update($user['id'], [
                            'otp_code' => $otpHash,
                            'otp_expiry' => $otpExpiry,
                        ]);

                        $subject = "Code de vérification - e-Media Support";
                        $body = "
                            <h2>Vérification de connexion</h2>
                            <p>Bonjour,</p>
                            <p>Votre code de connexion à 6 chiffres est :</p>
                            <p style=\"font-size: 28px; font-weight: bold; letter-spacing: 6px;\">{$otp}</p>
                            <p>Ce code est valable <strong>5 minutes</strong>.</p>
                            <p>Si vous n'êtes pas à l'origine de cette connexion, contactez immédiatement l'administrateur.</p>
                        ";
                        $sent = Email::send($user['email'], $subject, $body);

                        if (!$sent) {
                            $error = "Impossible d'envoyer le code de vérification. Veuillez réessayer.";
                        } else {
                            Audit::logAction('USER_LOGIN', 'User', $user['id'], $user['id'], [
                                'email' => $user['email'],
                                'role' => $user['role'],
                            ]);
                            Audit::logAction('OTP_SENT', 'User', $user['id'], $user['id'], [
                                'email' => $user['email'],
                                'role' => $user['role'],
                                'expires_in' => 300,
                            ]);

                            Session::set('2fa_user_id', $user['id']);
                            Session::set('2fa_email', $user['email']);
                            Helper::redirect('/2fa');
                        }
                    }
                } else {
                    $error = 'Identifiants incorrects.';
                }
            }
        }

        include dirname(__DIR__) . '/views/auth/login.php';
    }

    public function verify2fa(): void {
        Session::start();
        if (Auth::check()) {
            $user = Auth::user();
            $this->redirectByRole($user['role']);
        }

        $userId = Session::get('2fa_user_id');
        if (!$userId) {
            Helper::redirect('/login');
        }

        $user = User::findById($userId);
        if (!$user) {
            Session::remove('2fa_user_id');
            Session::remove('2fa_email');
            Helper::redirect('/login');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!Session::verifyCsrfToken($csrf)) {
                http_response_code(403);
                exit('CSRF token invalide');
            }

            $code = trim($_POST['code'] ?? '');
            $valid = !empty($user['otp_code'])
                && preg_match('/^\d{6}$/', $code) === 1
                && password_verify($code, $user['otp_code'])
                && !empty($user['otp_expiry'])
                && strtotime($user['otp_expiry']) > time();

            if ($valid) {
                User::update($user['id'], [
                    'otp_code' => null,
                    'otp_expiry' => null,
                ]);

                Session::remove('2fa_user_id');
                Session::remove('2fa_email');
                Session::set('user_id', $user['id']);
                Session::set('user', User::findById($user['id']));

                Audit::logAction('OTP_VERIFIED', 'User', $user['id'], $user['id'], [
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]);

                $this->redirectByRole($user['role']);
            } else {
                Audit::logAction('OTP_FAILED', 'User', $user['id'], $user['id'], [
                    'email' => $user['email'],
                    'role' => $user['role'],
                ]);
                $error = 'Code invalide ou expiré. Veuillez réessayer.';
            }
        }

        include dirname(__DIR__) . '/views/auth/2fa.php';
    }

    public function logout(): void {
        $user = Auth::user();
        if (!empty($user)) {
            Audit::logAction('USER_LOGOUT', 'User', $user['id'], $user['id'], [
                'email' => $user['email'],
                'role' => $user['role'],
            ]);
        }
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
                if (!$user) {
                    $error = 'Aucun compte associé à cette adresse email.';
                } else {
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

                    $sent = Email::send($email, $subject, $body);

                    Audit::logAction('PASSWORD_RESET_REQUESTED', 'User', $user['id'], $user['id'], [
                        'email' => $user['email'],
                        'email_sent' => $sent,
                    ]);

                    Session::set('success', 'Un lien de réinitialisation a été envoyé à votre adresse email.');
                    Helper::redirect('/login');
                }
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

                Audit::logAction('PASSWORD_RESET_COMPLETED', 'User', $user['id'], $user['id'], [
                    'email' => $user['email'],
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
