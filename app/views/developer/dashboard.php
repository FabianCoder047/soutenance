<?php
declare(strict_types=1);
$title = "Tableau de bord Développeur";
ob_start();
?>

<!-- KPI Stats Row -->
<div class="row g-4 mb-5">
    <div class="col-12 col-md-4">
        <div class="card-kpi bg-primary shadow-sm">
            <div class="kpi-val"><?= $projectsCount ?></div>
            <div class="kpi-label">Projets créés par moi</div>
            <i class="bi bi-folder-fill kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-md-4">
        <div class="card-kpi bg-warning shadow-sm">
            <div class="kpi-val"><?= $assignedCount ?></div>
            <div class="kpi-label">Mes Tasklists Actives</div>
            <i class="bi bi-check2-square kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-md-4">
        <div class="card-kpi bg-success shadow-sm">
            <div class="kpi-val"><?= $treatedCount ?></div>
            <div class="kpi-label">Tasklists traitées par moi</div>
            <i class="bi bi-patch-check-fill kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Main Sections -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-premium p-4 h-100">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-list-task text-primary"></i>
                Mes Tâches Récentes
            </h5>
            
            <?php if (empty($recentTasklists)): ?>
                <p class="text-muted">Aucune tâche ne vous est assignée actuellement.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>Tâche</th>
                                <th>Projet / Client</th>
                                <th>Statut</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTasklists as $task): ?>
                                <?php
                                    $statusClass = 'badge-pending';
                                    if ($task['status'] === 'IN_PROGRESS') $statusClass = 'badge-in_progress';
                                    if ($task['status'] === 'CLIENT_FILLED') $statusClass = 'badge-client_filled';
                                    if ($task['status'] === 'DONE') $statusClass = 'badge-done';

                                    $statusLabel = $task['status'];
                                    if ($task['status'] === 'PENDING') $statusLabel = 'En attente';
                                    if ($task['status'] === 'IN_PROGRESS') $statusLabel = 'En cours';
                                    if ($task['status'] === 'CLIENT_FILLED') $statusLabel = 'Réponse Client';
                                    if ($task['status'] === 'DONE') $statusLabel = 'Traité';
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= Helper::escape($task['title']) ?></div>
                                        <span class="text-muted small text-truncate d-inline-block" style="max-width: 200px;">
                                            <?= Helper::escape($task['description'] ?: 'Pas de description') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-block text-dark fw-semibold"><?= Helper::escape($task['project_name']) ?></span>
                                        <small class="text-muted"><?= Helper::escape($task['client_company']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-custom <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($task['status'] !== 'DONE'): ?>
                                            <form action="/admin/tasklists/treat" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                                <input type="hidden" name="tasklist_id" value="<?= $task['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                                    Traiter
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-success small fw-semibold"><i class="bi bi-check-lg"></i> Terminé</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card-premium p-4 h-100">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-lightning-charge-fill text-warning"></i>
                Actions Rapides
            </h5>
            <div class="d-grid gap-3">
                <a href="/admin/projects/create" class="btn btn-light border p-3 rounded-4 text-start d-flex align-items-center gap-3 transition-smooth hover-shadow">
                    <div class="bg-primary text-white rounded-3 p-2 d-inline-flex">
                        <i class="bi bi-folder-plus fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Nouveau Projet</div>
                        <small class="text-muted">Déclarer un espace projet client</small>
                    </div>
                </a>

                <a href="/admin/tasklists/create" class="btn btn-light border p-3 rounded-4 text-start d-flex align-items-center gap-3 transition-smooth hover-shadow">
                    <div class="bg-warning text-white rounded-3 p-2 d-inline-flex">
                        <i class="bi bi-plus-circle-fill fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Créer Tasklist</div>
                        <small class="text-muted">Créer une fiche de tâches (auto-assignée)</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/developer.php';
?>
