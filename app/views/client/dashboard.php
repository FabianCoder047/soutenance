<?php
declare(strict_types=1);
$title = "Tableau de bord Client";
ob_start();
?>

<!-- KPI Stats Row -->
<div class="row g-4 mb-5">
    <div class="col-12 col-md-4">
        <div class="card-kpi bg-primary shadow-sm">
            <div class="kpi-val"><?= $projectsCount ?></div>
            <div class="kpi-label">Mes Projets Actifs</div>
            <i class="bi bi-folder-fill kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-md-4">
        <div class="card-kpi bg-warning shadow-sm">
            <div class="kpi-val"><?= $pendingCount ?></div>
            <div class="kpi-label">Tâches en attente de réponse</div>
            <i class="bi bi-chat-left-text-fill kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-md-4">
        <div class="card-kpi bg-info shadow-sm">
            <div class="kpi-val"><?= $pendingWishlistsCount ?></div>
            <div class="kpi-label">Mes Demandes en Attente</div>
            <i class="bi bi-magic kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Main Section -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-premium p-4 h-100">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill text-warning"></i>
                Tâches Nécessitant Votre Attention
            </h5>
            
            <?php if (empty($recentTasklists)): ?>
                <p class="text-muted small my-3">Parfait ! Aucune tâche ne requiert votre réponse actuellement.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>Tâche</th>
                                <th>Projet</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTasklists as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= Helper::escape($task['title']) ?></div>
                                        <span class="text-muted small"><?= Helper::escape($task['description'] ?: 'Pas de description') ?></span>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold"><?= Helper::escape($task['project_name']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/tasklists/show?id=<?= urlencode($task['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-eye"></i> Détails
                                        </a>
                                        <a href="/client/tasklists/fill?id=<?= urlencode($task['id']) ?>" class="btn btn-sm btn-warning rounded-pill px-3">
                                            <i class="bi bi-pencil-square"></i> Répondre
                                        </a>
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
                Actions Client
            </h5>
            <div class="d-grid gap-3">
                <a href="/client/wishlists/new" class="btn btn-light border p-3 rounded-4 text-start d-flex align-items-center gap-3 transition-smooth hover-shadow">
                    <div class="bg-primary text-white rounded-3 p-2 d-inline-flex">
                        <i class="bi bi-magic fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Nouvelle Demande</div>
                        <small class="text-muted">Ajouter un besoin à la liste d'attente</small>
                    </div>
                </a>

                <a href="/client/projects" class="btn btn-light border p-3 rounded-4 text-start d-flex align-items-center gap-3 transition-smooth hover-shadow">
                    <div class="bg-success text-white rounded-3 p-2 d-inline-flex">
                        <i class="bi bi-folder-fill fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Mes Projets</div>
                        <small class="text-muted">Voir l'avancement de vos projets</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/client.php';
?>
