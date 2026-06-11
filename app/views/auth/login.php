<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | e-Media Support</title>
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
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .form-control {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
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
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width: 60px; height: 60px; background-color: #1e3a5f !important;">
                <i class="bi bi-briefcase fs-3"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">e-Media Support</h3>
            <p class="text-muted">Connectez-vous pour accéder à votre espace</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= Helper::escape($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (Session::has('success')): ?>
            <div class="alert alert-success border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-check-circle-fill"></i>
                <div><?= Session::get('success') ?></div>
            </div>
            <?php Session::remove('success'); ?>
        <?php endif; ?>

        <form action="/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
            
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold text-muted">Adresse Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 bg-light" id="email" name="email" value="<?= Helper::escape($email ?? '') ?>" required placeholder="nom@exemple.com">
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between mb-1">
                    <label for="password" class="form-label fw-semibold text-muted mb-0">Mot de passe</label>
                    <a href="/reset-password" class="text-decoration-none small fw-semibold text-primary">Mot de passe oublié ?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 bg-light" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-primary">Se Connecter</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
