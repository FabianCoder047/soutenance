<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification | e-Media Support</title>
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .otp-input {
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font-size: 1.5rem;
            letter-spacing: 10px;
            text-align: center;
            font-weight: 600;
        }
        .otp-input:focus {
            border-color: #1e3a5f;
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.15);
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
            <img src="/assets/img/logo.png" alt="Logo e-Media Support" class="mb-3" style="width: 70px; height: 70px; object-fit: contain;">
            <h3 class="fw-bold text-dark mb-1">Vérification de sécurité</h3>
            <p class="text-muted small mb-0">Un code à 6 chiffres vous a été envoyé par email.</p>
            <p class="text-muted small">Durée de validité : <strong>5 minutes</strong></p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= Helper::escape($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="/2fa" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">

            <div class="mb-4">
                <label for="code" class="form-label fw-semibold text-muted">Code de vérification</label>
                <input type="text"
                       class="form-control otp-input"
                       id="code"
                       name="code"
                       inputmode="numeric"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       autocomplete="one-time-code"
                       required
                       placeholder="000000">
                <div class="form-text text-center mt-2">
                    Vérifiez votre boîte de réception et votre dossier spam.
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-shield-lock me-1"></i> Vérifier
                </button>
            </div>

            <div class="text-center">
                <a href="/login" class="text-decoration-none small fw-semibold text-muted">
                    <i class="bi bi-arrow-left"></i> Retour à la connexion
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>