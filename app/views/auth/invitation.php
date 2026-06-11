<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compléter votre inscription | e-Media Support</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        .form-control {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
        }
        .btn-primary {
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            background-color: #1e3a5f;
            border: none;
        }
        .btn-primary:hover {
            background-color: #12243d;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark mb-1">Compléter votre profil</h3>
            <p class="text-muted">Activez votre compte sur e-Media Support</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= Helper::escape($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-check-circle-fill"></i>
                <div><?= Helper::escape($success) ?></div>
            </div>
            <div class="text-center">
                <a href="/login" class="btn btn-primary w-100">Se Connecter</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info border-0 rounded-3 p-3 mb-3" style="font-size: 0.85rem;">
                <i class="bi bi-info-circle-fill me-1"></i>
                Rôle : <strong><?= Helper::escape($user['role']) ?></strong><br>
                Email : <strong><?= Helper::escape($user['email']) ?></strong>
            </div>

            <form action="/invitation" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <input type="hidden" name="token" value="<?= Helper::escape($token) ?>">
                
                <?php if (($user['role'] ?? '') === 'CLIENT'): ?>
                    <!-- Client specific fields -->
                    <div class="mb-3">
                        <label for="company_name" class="form-label fw-semibold text-muted">Nom de l'entreprise</label>
                        <input type="text" class="form-control bg-light" id="company_name" name="company_name" required value="<?= Helper::escape($_POST['company_name'] ?? '') ?>" placeholder="Ex: e-Media SARL">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label fw-semibold text-muted">Adresse</label>
                        <textarea class="form-control bg-light" id="address" name="address" rows="3" required placeholder="Adresse de l'entreprise..."><?= Helper::escape($_POST['address'] ?? '') ?></textarea>
                    </div>
                <?php else: ?>
                    <!-- Admin / Dev specific fields -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="first_name" class="form-label fw-semibold text-muted">Prénom</label>
                            <input type="text" class="form-control bg-light" id="first_name" name="first_name" required value="<?= Helper::escape($_POST['first_name'] ?? '') ?>" placeholder="Ex: Jean">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label fw-semibold text-muted">Nom</label>
                            <input type="text" class="form-control bg-light" id="last_name" name="last_name" required value="<?= Helper::escape($_POST['last_name'] ?? '') ?>" placeholder="Ex: DUPONT">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold text-muted">Choisissez un mot de passe</label>
                    <input type="password" class="form-control bg-light" id="password" name="password" required placeholder="Min. 8 caractères, 1 maj + 1 chiffre">
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-semibold text-muted">Confirmez le mot de passe</label>
                    <input type="password" class="form-control bg-light" id="password_confirm" name="password_confirm" required placeholder="Confirmer le mot de passe">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Enregistrer mon compte</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
