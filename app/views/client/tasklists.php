<?php
declare(strict_types=1);
$title = "Suivi des Tasklists";
ob_start();
?>

<?php if (!empty($filteredProject)): ?>
    <div class="alert alert-info border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-folder2-open me-2"></i> Tasklists du projet <strong><?= Helper::escape($filteredProject['name']) ?></strong></span>
        <a href="/client/tasklists" class="btn btn-sm btn-light rounded-pill">Tout afficher</a>
    </div>
<?php endif; ?>

<!-- Filters Header -->
<div class="card-premium p-4 mb-4">
    <form action="/client/tasklists" method="GET" class="row g-2 align-items-center">
        <?php if (!empty($filters['project_id'])): ?>
            <input type="hidden" name="project_id" value="<?= Helper::escape($filters['project_id']) ?>">
        <?php endif; ?>
        <div class="col-auto">
            <select name="status" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                <option value="">Tous les statuts</option>
                <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>En cours</option>
                <option value="CLIENT_FILLED" <?= ($filters['status'] ?? '') === 'CLIENT_FILLED' ? 'selected' : '' ?>>Réponse envoyée</option>
                <option value="DONE" <?= ($filters['status'] ?? '') === 'DONE' ? 'selected' : '' ?>>Traitée</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm"><i class="bi bi-filter"></i> Filtrer</button>
            <?php if (!empty($filters['status']) || !empty($filters['project_id'])): ?>
                <a href="/client/tasklists" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tasklists Table -->
<div class="card-premium p-0">
    <div class="table-responsive table-responsive-tasklists">
        <table class="table table-premium table-tasklists m-0">
            <thead>
                <tr>
                    <th>Détails de la tâche</th>
                    <th>Projet</th>
                    <th>Statut</th>
                    <th>Dates</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasklists)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Aucune tasklist trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasklists as $t): ?>
                        <tr>
                            <td>
                                <div>
                                    <a href="/tasklists/show?id=<?= urlencode($t['id']) ?>" class="fw-bold text-dark mb-1 text-decoration-none d-inline-block"><?= Helper::escape($t['title']) ?></a>
                                    <small class="text-muted d-block"><?= Helper::escape($t['description'] ?: 'Aucune description') ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= Helper::escape($t['project_name']) ?></span>
                            </td>
                            <td class="col-status">
                                <?php $status = $t['status']; include dirname(__DIR__) . '/partials/tasklist_status.php'; ?>
                            </td>
                            <td class="col-dates">
                                <?php include dirname(__DIR__) . '/partials/tasklist_dates.php'; ?>
                            </td>
                            <td class="text-end col-actions">
                                <?php include dirname(__DIR__) . '/partials/tasklist_list_actions.php'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/client.php';
?>
