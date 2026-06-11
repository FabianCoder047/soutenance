<?php
declare(strict_types=1);
$title = "Nouvelle Demande";
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-premium p-4 p-md-5">
            <h5 class="fw-bold mb-4">Soumettre une nouvelle demande (Wishlist)</h5>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= Helper::escape($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="/client/wishlists/new" method="POST" class="form-premium">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                
                <div class="mb-4">
                    <label for="project_id" class="form-label fw-semibold">Associer à un projet (facultatif)</label>
                    <select class="form-select" id="project_id" name="project_id">
                        <option value="">-- Demande générale (aucun projet en particulier) --</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($projectId ?? '') === $proj['id'] ? 'selected' : '' ?>>
                                <?= Helper::escape($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="content" class="form-label fw-semibold">Votre besoin / Liste d'attentes</label>
                    <textarea class="form-control" id="content" name="content" rows="6" required placeholder="Saisissez précisément votre demande, vos attentes, ou les modifications souhaitées..."><?= Helper::escape($content_val ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-5">
                    <a href="/client/wishlists" class="btn btn-light rounded-pill px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/client.php';
?>
