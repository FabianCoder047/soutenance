<?php
declare(strict_types=1);
$title = "Tableau de bord";
ob_start();
?>

<!-- KPI Stats Row -->
<div class="row g-4 mb-5">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-kpi bg-primary shadow-sm">
            <div class="kpi-val"><?= $projectsCount ?></div>
            <div class="kpi-label">Projets Actifs</div>
            <i class="bi bi-folder-fill kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-kpi bg-warning shadow-sm">
            <div class="kpi-val"><?= $tasklistsCount ?></div>
            <div class="kpi-label">Tasklists en Cours</div>
            <i class="bi bi-check2-square kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-kpi bg-success shadow-sm">
            <div class="kpi-val"><?= $clientsCount ?></div>
            <div class="kpi-label">Clients Actifs</div>
            <i class="bi bi-people-fill kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-kpi bg-info shadow-sm">
            <div class="kpi-val"><?= $wishlistsCount ?></div>
            <div class="kpi-label">Wishlists en Attente</div>
            <i class="bi bi-magic kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Main Dashboard Sections -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-premium p-4 h-100">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-primary"></i>
                Activités Récentes du Système
            </h5>
            
            <?php if (empty($recentLogs)): ?>
                <p class="text-muted">Aucune activité enregistrée pour le moment.</p>
            <?php else: ?>
                <div class="timeline">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                            <li class="list-group-item px-0 py-3 border-light">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge rounded-pill bg-light text-dark border me-2" style="font-size: 0.75rem;">
                                            <?= Helper::escape($log['action']) ?>
                                        </span>
                                        <span class="text-muted small"><?= Helper::escape($log['email']) ?></span>
                                        <p class="mb-0 mt-2 small text-dark">
                                            Entité: <strong><?= Helper::escape($log['entity_type']) ?></strong> 
                                            (ID: <span class="font-monospace text-muted"><?= substr(Helper::escape($log['entity_id']), 0, 8) ?>...</span>)
                                        </p>
                                        <?php if (!empty($log['metadata'])): ?>
                                            <div class="bg-light rounded p-2 mt-2 small font-monospace" style="font-size: 0.8rem;">
                                                <?= Helper::escape($log['metadata']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted ms-2"><?= Helper::formatDateTime($log['created_at']) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="mt-4 text-end">
                    <a href="/admin/audit" class="btn btn-sm btn-outline-primary rounded-pill px-3">Voir tout l'historique</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="card-premium p-4 h-100 bg-white">
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
                        <small class="text-muted">Créer un espace projet pour un client</small>
                    </div>
                </a>

                <a href="/admin/tasklists/create" class="btn btn-light border p-3 rounded-4 text-start d-flex align-items-center gap-3 transition-smooth hover-shadow">
                    <div class="bg-warning text-white rounded-3 p-2 d-inline-flex">
                        <i class="bi bi-check2-circle fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Nouvelle Tasklist</div>
                        <small class="text-muted">Créer une fiche de tâches et l'attribuer</small>
                    </div>
                </a>

                <a href="/admin/users" class="btn btn-light border p-3 rounded-4 text-start d-flex align-items-center gap-3 transition-smooth hover-shadow">
                    <div class="bg-success text-white rounded-3 p-2 d-inline-flex">
                        <i class="bi bi-person-plus fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Inviter un Utilisateur</div>
                        <small class="text-muted">Générer un lien d'inscription par email</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
?>
