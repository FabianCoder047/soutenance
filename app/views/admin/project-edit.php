<?php
declare(strict_types=1);
$title = "Modifier le Projet";
$layout = Auth::user()['role'] === 'ADMIN' ? 'admin' : 'developer';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-premium p-4 p-md-5">
            <h5 class="fw-bold mb-4">Modifier le projet</h5>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= Helper::escape($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="/admin/projects/edit" method="POST" class="form-premium">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <input type="hidden" name="id" value="<?= Helper::escape($project['id']) ?>">
                
                <div class="mb-4">
                    <label for="name" class="form-label fw-semibold">Nom du projet</label>
                    <input type="text" class="form-control" id="name" name="name" required value="<?= Helper::escape($project['name']) ?>" placeholder="Ex: Refonte du Site e-Commerce">
                </div>

                <div class="mb-4">
                    <label for="client_id" class="form-label fw-semibold">Client associé</label>
                    <select class="form-select" id="client_id" name="client_id" required>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $project['client_id'] === $c['id'] ? 'selected' : '' ?>>
                                <?= Helper::escape($c['company_name'] ?? $c['email']) ?> (<?= Helper::escape($c['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label fw-semibold">Statut</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="ACTIVE" <?= $project['status'] === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                        <option value="ARCHIVED" <?= $project['status'] === 'ARCHIVED' ? 'selected' : '' ?>>Archivé</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description du projet</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Décrivez les objectifs..."><?= Helper::escape($project['description'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-5">
                    <a href="<?= $layout === 'admin' ? '/admin/projects' : '/developer/my-projects' ?>" class="btn btn-light rounded-pill px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/' . $layout . '.php';
?>
