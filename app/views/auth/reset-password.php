<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié | e-Media Support</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
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
            <h3 class="fw-bold text-dark mb-1">Mot de passe oublié</h3>
            <p class="text-muted">Saisissez votre email pour recevoir un lien de réinitialisation</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= Helper::escape($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-check-circle-fill"></i>
                <div><?= Helper::escape($success) ?></div>
            </div>
        <?php endif; ?>

        <form action="/reset-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
            
            <div class="mb-4">
                <label for="email" class="form-label fw-semibold text-muted">Adresse Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 bg-light" id="email" name="email" required placeholder="nom@exemple.com">
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">Envoyer la demande</button>
            </div>
            
            <div class="text-center">
                <a href="/login" class="text-decoration-none small fw-semibold text-muted"><i class="bi bi-arrow-left"></i> Retour au login</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
