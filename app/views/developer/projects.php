<?php
declare(strict_types=1);
$title = "Tous les Projets Actifs";
ob_start();
?>

<!-- Projects List -->
<?php if (empty($projects)): ?>
    <div class="card-premium p-5 text-center text-muted">
        <i class="bi bi-folder-x fs-1 mb-3 text-secondary"></i>
        <h5>Aucun projet actif dans le système</h5>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($projects as $p): ?>
            <?php 
                $tasklists = Tasklist::getAll(['project_id' => $p['id']]);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-premium p-4 h-100 d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge-custom badge-done mb-2 d-inline-block">Actif</span>
                        <h5 class="fw-bold text-dark mb-1"><?= Helper::escape($p['name']) ?></h5>
                        <small class="text-muted d-block mb-2">Client : <strong><?= Helper::escape($p['client_company'] ?? $p['client_email']) ?></strong></small>
                    </div>
                    
                    <p class="text-muted font-secondary small flex-grow-1" style="max-height: 80px; overflow-y: auto;">
                        <?= Helper::escape($p['description'] ?: 'Aucune description fournie.') ?>
                    </p>
                    
                    <hr class="my-3 text-muted">
                    
                    <div class="mt-auto">
                        <span class="small text-muted d-block mb-2"><i class="bi bi-list-task me-1"></i> <?= count($tasklists) ?> tasklists</span>
                        <div class="d-flex flex-nowrap gap-2">
                            <a href="/developer/my-tasklists?project_id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap">
                                <i class="bi bi-eye me-1"></i> Voir tasklists
                            </a>
                            <a href="/admin/tasklists/create?project_id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap">
                                Créer tasklist
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/developer.php';
?>
