<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Validator.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/models/User.php';

class InvitationController {
    public function complete(): void {
        Session::start();
        
        $token = $_GET['token'] ?? $_POST['token'] ?? null;
        if (!$token) {
            Helper::redirect('/login');
        }

        $user = User::findByInviteToken($token);
        if (!$user) {
            $error = "Ce lien d'invitation est invalide ou a expiré. Veuillez contacter votre administrateur.";
            include dirname(__DIR__) . '/views/auth/invitation.php';
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
            
            $updateData = [];

            if ($user['role'] === 'CLIENT') {
                $companyName = trim($_POST['company_name'] ?? '');
                $address = trim($_POST['address'] ?? '');

                if (empty($companyName) || empty($address) || empty($password) || empty($confirm)) {
                    $error = "Veuillez remplir tous les champs.";
                } else {
                    $updateData['company_name'] = $companyName;
                    $updateData['address'] = $address;
                }
            } else {
                // Admin or Dev
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');

                if (empty($firstName) || empty($lastName) || empty($password) || empty($confirm)) {
                    $error = "Veuillez remplir tous les champs.";
                } else {
                    $updateData['first_name'] = $firstName;
                    $updateData['last_name'] = $lastName;
                }
            }

            if (!$error) {
                if ($password !== $confirm) {
                    $error = "Les mots de passe ne correspondent pas.";
                } else if (!Validator::password($password)) {
                    $error = "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.";
                } else {
                    // Update user
                    $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $updateData['status'] = 'ACTIVE';
                    $updateData['profile_complete'] = 1;
                    $updateData['invite_token'] = null;
                    $updateData['invite_expiry'] = null;

                    if (User::update($user['id'], $updateData)) {
                        $success = "Votre profil a été complété avec succès ! Vous pouvez maintenant vous connecter.";
                    } else {
                        $error = "Une erreur est survenue lors de la mise à jour de votre profil.";
                    }
                }
            }
        }

        include dirname(__DIR__) . '/views/auth/invitation.php';
    }
}
