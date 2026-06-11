<?php
declare(strict_types=1);
$title = "Journal d'Audit";
ob_start();
?>

<!-- Filters Form -->
<div class="card-premium p-4 mb-4">
    <form action="/admin/audit" method="GET" class="row g-2 align-items-end">
        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-bold text-muted">Utilisateur</label>
            <select name="user_id" class="form-select rounded-pill" style="font-size: 0.9rem;">
                <option value="">Tous les utilisateurs</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] ?? '') === $u['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($u['first_name'] || $u['last_name'] ? ($u['first_name'] . ' ' . $u['last_name']) : ($u['company_name'] ?: $u['email'])) ?> (<?= Helper::escape($u['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-bold text-muted">Action</label>
            <select name="action" class="form-select rounded-pill" style="font-size: 0.9rem;">
                <option value="">Toutes les actions</option>
                <option value="PROJECT_CREATED" <?= ($filters['action'] ?? '') === 'PROJECT_CREATED' ? 'selected' : '' ?>>PROJECT_CREATED</option>
                <option value="PROJECT_ARCHIVED" <?= ($filters['action'] ?? '') === 'PROJECT_ARCHIVED' ? 'selected' : '' ?>>PROJECT_ARCHIVED</option>
                <option value="TASKLIST_CREATED" <?= ($filters['action'] ?? '') === 'TASKLIST_CREATED' ? 'selected' : '' ?>>TASKLIST_CREATED</option>
                <option value="TASKLIST_PUBLISHED" <?= ($filters['action'] ?? '') === 'TASKLIST_PUBLISHED' ? 'selected' : '' ?>>TASKLIST_PUBLISHED</option>
                <option value="TASKLIST_ASSIGNED" <?= ($filters['action'] ?? '') === 'TASKLIST_ASSIGNED' ? 'selected' : '' ?>>TASKLIST_ASSIGNED</option>
                <option value="TASKLIST_TREATED" <?= ($filters['action'] ?? '') === 'TASKLIST_TREATED' ? 'selected' : '' ?>>TASKLIST_TREATED</option>
                <option value="TASKLIST_CLIENT_FILLED" <?= ($filters['action'] ?? '') === 'TASKLIST_CLIENT_FILLED' ? 'selected' : '' ?>>TASKLIST_CLIENT_FILLED</option>
                <option value="USER_INVITED" <?= ($filters['action'] ?? '') === 'USER_INVITED' ? 'selected' : '' ?>>USER_INVITED</option>
                <option value="USER_SUSPENDED" <?= ($filters['action'] ?? '') === 'USER_SUSPENDED' ? 'selected' : '' ?>>USER_SUSPENDED</option>
                <option value="USER_REACTIVATED" <?= ($filters['action'] ?? '') === 'USER_REACTIVATED' ? 'selected' : '' ?>>USER_REACTIVATED</option>
                <option value="WISHLIST_CREATED" <?= ($filters['action'] ?? '') === 'WISHLIST_CREATED' ? 'selected' : '' ?>>WISHLIST_CREATED</option>
            </select>
        </div>

        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-bold text-muted">Type d'entité</label>
            <select name="entity_type" class="form-select rounded-pill" style="font-size: 0.9rem;">
                <option value="">Toutes les entités</option>
                <option value="Project" <?= ($filters['entity_type'] ?? '') === 'Project' ? 'selected' : '' ?>>Project</option>
                <option value="Tasklist" <?= ($filters['entity_type'] ?? '') === 'Tasklist' ? 'selected' : '' ?>>Tasklist</option>
                <option value="User" <?= ($filters['entity_type'] ?? '') === 'User' ? 'selected' : '' ?>>User</option>
                <option value="Wishlist" <?= ($filters['entity_type'] ?? '') === 'Wishlist' ? 'selected' : '' ?>>Wishlist</option>
            </select>
        </div>

        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-bold text-muted">Date</label>
            <input type="date" name="date" class="form-control rounded-pill" style="font-size: 0.9rem;" value="<?= Helper::escape($filters['date'] ?? '') ?>">
        </div>

        <div class="col-md-2 col-sm-12 text-md-end mt-2 mt-md-0">
            <div class="d-inline-flex gap-2 w-100">
                <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm w-50"><i class="bi bi-filter"></i> Filtrer</button>
                <a href="/admin/audit" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm w-50">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Timeline List -->
<div class="card-premium p-4">
    <?php if (empty($logs)): ?>
        <p class="text-center text-muted my-4">Aucun enregistrement d'audit ne correspond aux critères.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-premium m-0">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Action</th>
                        <th>Utilisateur</th>
                        <th>Type d'entité & ID</th>
                        <th>Détails (Métadonnées)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><span class="small font-monospace text-muted"><?= Helper::formatDateTime($log['created_at']) ?></span></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    <?= Helper::escape($log['action']) ?>
                                </span>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-semibold text-dark d-block">
                                        <?= Helper::escape($log['user_first'] || $log['user_last'] ? ($log['user_first'] . ' ' . $log['user_last']) : ($log['user_company'] ?: 'Système')) ?>
                                    </span>
                                    <small class="text-muted"><?= Helper::escape($log['user_email']) ?> (<?= Helper::escape($log['user_role']) ?>)</small>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="fw-bold d-block text-muted"><?= Helper::escape($log['entity_type']) ?></span>
                                    <span class="font-monospace text-muted" style="font-size: 0.8rem;"><?= Helper::escape($log['entity_id']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($log['metadata'])): ?>
                                    <pre class="bg-light p-2 rounded-3 text-dark mb-0 font-monospace" style="font-size: 0.8rem; white-space: pre-wrap;"><?= Helper::escape(json_encode(json_decode($log['metadata']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                <?php else: ?>
                                    <span class="text-muted small">Aucun détail supplémentaire</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
?>
