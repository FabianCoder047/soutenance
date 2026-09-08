<?php
declare(strict_types=1);
$title = "Mes Projets";
ob_start();
?>

<!-- Projects List -->
<?php if (empty($projects)): ?>
    <div class="card-premium p-5 text-center text-muted">
        <i class="bi bi-folder-x fs-1 mb-3 text-secondary"></i>
        <h5>Aucun projet n'est enregistré pour votre entreprise</h5>
        <p class="mb-0 text-muted">Contactez l'administrateur e-Media Support pour initier un projet.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($projects as $p): ?>
            <?php 
                // Fetch tasklists of this project
                $tasklists = Tasklist::getAll(['project_id' => $p['id']]);
                
                $statusClass = $p['status'] === 'ACTIVE' ? 'badge-done' : 'badge-suspended';
                $statusLabel = $p['status'] === 'ACTIVE' ? 'Actif' : 'Archivé';
            ?>
            <div class="col-12">
                <div class="card-premium p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <span class="badge-custom <?= $statusClass ?> mb-2 d-inline-block"><?= $statusLabel ?></span>
                            <h4 class="fw-bold text-dark mb-1"><?= Helper::escape($p['name']) ?></h4>
                            <small class="text-muted">Créé le : <strong><?= Helper::formatDateTime($p['created_at']) ?></strong></small>
                        </div>
                        <div class="d-flex flex-nowrap gap-2 align-items-center">
                            <a href="/client/tasklists?project_id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap">
                                <i class="bi bi-list-task me-1"></i> Voir tasklists
                            </a>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProj-<?= $p['id'] ?>" aria-expanded="false" aria-controls="collapseProj-<?= $p['id'] ?>">
                                <i class="bi bi-eye-fill me-1"></i> Détails (<?= count($tasklists) ?>)
                            </button>
                        </div>
                    </div>
                    
                    <p class="text-muted font-secondary mb-3"><?= Helper::escape($p['description'] ?: 'Aucune description fournie.') ?></p>
                    
                    <!-- Collapsible details: Tasklists of this project -->
                    <div class="collapse mt-4" id="collapseProj-<?= $p['id'] ?>">
                        <div class="border-top pt-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-list-task me-1 text-primary"></i> Suivi des tâches :</h6>
                            <?php if (empty($tasklists)): ?>
                                <p class="text-muted small my-2">Aucune tasklist n'est en cours pour ce projet.</p>
                            <?php else: ?>
                                <ul class="list-group rounded-3 border-light">
                                    <?php foreach ($tasklists as $t): ?>
                                        <?php
                                            $tStatusClass = 'badge-pending';
                                            if ($t['status'] === 'IN_PROGRESS') $tStatusClass = 'badge-in_progress';
                                            if ($t['status'] === 'CLIENT_FILLED') $tStatusClass = 'badge-client_filled';
                                            if ($t['status'] === 'DONE') $tStatusClass = 'badge-done';

                                            $tStatusLabel = $t['status'];
                                            if ($t['status'] === 'PENDING') $tStatusLabel = 'En attente';
                                            if ($t['status'] === 'IN_PROGRESS') $tStatusLabel = 'En cours';
                                            if ($t['status'] === 'CLIENT_FILLED') $tStatusLabel = 'Réponse Client envoyée';
                                            if ($t['status'] === 'DONE') $tStatusLabel = 'Traité';
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center border-light py-3 px-3">
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= Helper::escape($t['title']) ?></div>
                                                <small class="text-muted d-block"><?= Helper::escape($t['description'] ?: 'Aucune description') ?></small>
                                                <?php if ($t['client_filled'] && !empty($t['client_response'])): ?>
                                                    <div class="bg-light p-2 rounded-3 my-2 small border-start border-3 border-success">
                                                        <strong>Votre réponse :</strong> <?= Helper::escape($t['client_response']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge-custom <?= $tStatusClass ?>" style="font-size: 0.7rem;"><?= $tStatusLabel ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/client.php';
?>
