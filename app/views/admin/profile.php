<?php
declare(strict_types=1);
$title = "Mon Profil";
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-premium p-4 p-md-5">
            <h5 class="fw-bold mb-4">Mettre à jour mes informations</h5>
            
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= Helper::escape($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><?= Helper::escape($success) ?></div>
                </div>
            <?php endif; ?>

            <form action="/admin/profile" method="POST" class="form-premium">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Adresse Email (non modifiable)</label>
                    <input type="email" class="form-control bg-light border-0" value="<?= Helper::escape($user['email']) ?>" readonly>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="first_name" class="form-label fw-semibold">Prénom</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required value="<?= Helper::escape($user['first_name'] ?? '') ?>" placeholder="Ex: Jean">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold">Nom</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required value="<?= Helper::escape($user['last_name'] ?? '') ?>" placeholder="Ex: DUPONT">
                    </div>
                </div>

                <hr class="my-4 text-muted">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-key-fill text-warning me-1"></i> Modifier le mot de passe (laisser vide pour ne pas changer)</h6>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Nouveau mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 caractères, 1 maj + 1 chiffre">
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-semibold">Confirmer le nouveau mot de passe</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirmer le mot de passe">
                </div>

                <div class="d-flex justify-content-end mt-5">
                    <button type="submit" class="btn btn-primary rounded-pill px-5">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
?>
