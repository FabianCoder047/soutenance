<?php
declare(strict_types=1);
$title = "Gestion des Tasklists";
ob_start();
?>

<!-- Filter & Actions Header -->
<div class="card-premium p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <form action="/admin/tasklists" method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-0 flex-grow-1">
            <select name="project_id" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem; width: auto;">
                <option value="">Tous les projets</option>
                <?php foreach ($projects as $proj): ?>
                    <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] ?? '') === $proj['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($proj['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem; width: auto;">
                <option value="">Tous les statuts</option>
                <option value="DRAFT" <?= ($filters['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>>Brouillon</option>
                <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>En cours</option>
                <option value="CLIENT_FILLED" <?= ($filters['status'] ?? '') === 'CLIENT_FILLED' ? 'selected' : '' ?>>Complété par Client</option>
                <option value="DONE" <?= ($filters['status'] ?? '') === 'DONE' ? 'selected' : '' ?>>Traité</option>
            </select>
            <select name="assigned_to_id" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem; width: auto;">
                <option value="">Tous les assignés</option>
                <?php foreach ($devs as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($filters['assigned_to_id'] ?? '') === $d['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($d['first_name'] . ' ' . $d['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm"><i class="bi bi-filter"></i> Filtrer</button>
                <?php if (!empty($filters['project_id']) || !empty($filters['status']) || !empty($filters['assigned_to_id'])): ?>
                    <a href="/admin/tasklists" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</a>
                <?php endif; ?>
            </div>
        </form>
        <a href="/admin/tasklists/create" class="btn btn-primary rounded-pill px-4 shadow-sm text-nowrap">
            <i class="bi bi-plus-circle me-1"></i> Créer Tasklist
        </a>
    </div>
</div>

<!-- Tasklists Table -->
<div class="card-premium p-0">
    <div class="table-responsive table-responsive-tasklists">
        <table class="table table-premium table-tasklists m-0">
            <thead>
                <tr>
                    <th>Détails de la Tâche</th>
                    <th>Projet</th>
                    <th>Assigné à</th>
                    <th>Statut</th>
                    <th>Dates</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasklists)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Aucune tasklist trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasklists as $t): ?>
                        <tr>
                            <td>
                                <div>
                                    <a href="/tasklists/show?id=<?= urlencode($t['id']) ?>" class="fw-bold text-dark mb-1 text-decoration-none d-inline-block">
                                        <?= Helper::escape($t['title']) ?>
                                    </a>
                                    <small class="text-muted d-block"><?= Helper::escape($t['description'] ?: 'Aucune description') ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-semibold text-dark d-block"><?= Helper::escape($t['project_name']) ?></span>
                                    <small class="text-muted"><?= Helper::escape($t['client_company']) ?></small>
                                </div>
                            </td>
                            <td>
                                <!-- Assign Select (Auto-submits onchange) -->
                                <form action="/admin/tasklists/assign" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                    <input type="hidden" name="tasklist_id" value="<?= $t['id'] ?>">
                                    <select name="assigned_to_id" class="form-select form-select-sm rounded-pill font-monospace" style="font-size: 0.8rem; width: 160px;" onchange="this.form.submit()">
                                        <?php foreach ($devs as $d): ?>
                                            <option value="<?= $d['id'] ?>" <?= $t['assigned_to_id'] === $d['id'] ? 'selected' : '' ?>>
                                                <?= Helper::escape($d['first_name'] . ' ' . $d['last_name']) ?> (<?= Helper::escape($d['role']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
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
    <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
</div>

<?php
include dirname(__DIR__) . '/partials/publish_tasklist_modal.php';
include dirname(__DIR__) . '/partials/delete_tasklist_modal.php';
?>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
?>
