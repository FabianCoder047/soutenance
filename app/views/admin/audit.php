<?php
declare(strict_types=1);
$title = "Journal d'Audit";
ob_start();

$actions = Helper::auditActions();
$entities = Helper::auditEntities();
?>

<!-- Filters Form -->
<div class="card-premium p-4 mb-4">
    <form action="/admin/audit" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-bold text-muted">Utilisateur</label>
            <select name="user_id" class="form-select rounded-pill" style="font-size: 0.9rem;">
                <option value="">Tous les utilisateurs</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] ?? '') === $u['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($u['first_name'] || $u['last_name'] ? ($u['last_name'] . ' ' . $u['first_name']) : ($u['company_name'] ?: $u['email'])) ?> (<?= Helper::escape($u['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-bold text-muted">Action</label>
            <select name="action" class="form-select rounded-pill" style="font-size: 0.9rem;">
                <option value="">Toutes les actions</option>
                <?php foreach ($actions as $code => $label): ?>
                    <option value="<?= Helper::escape($code) ?>" <?= ($filters['action'] ?? '') === $code ? 'selected' : '' ?>><?= Helper::escape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-bold text-muted">Type d'entité</label>
            <select name="entity_type" class="form-select rounded-pill" style="font-size: 0.9rem;">
                <option value="">Toutes</option>
                <?php foreach ($entities as $code => $label): ?>
                    <option value="<?= Helper::escape($code) ?>" <?= ($filters['entity_type'] ?? '') === $code ? 'selected' : '' ?>><?= Helper::escape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-bold text-muted">Période (Du)</label>
            <input type="date" name="date_from" class="form-control rounded-pill" style="font-size: 0.9rem;" value="<?= Helper::escape($filters['date_from'] ?? '') ?>">
        </div>

        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-bold text-muted">Au</label>
            <input type="date" name="date_to" class="form-control rounded-pill" style="font-size: 0.9rem;" value="<?= Helper::escape($filters['date_to'] ?? '') ?>">
        </div>

        <div class="col-12 d-flex justify-content-md-end gap-2 mt-3 mt-md-0">
            <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-filter"></i> Filtrer
            </button>
            <a href="/admin/audit" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
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
                        <th>Entité</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><span class="small font-monospace text-muted"><?= Helper::formatDateTime($log['created_at']) ?></span></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    <?= Helper::escape(Helper::actionLabel($log['action'])) ?>
                                </span>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-semibold text-dark d-block">
                                        <?= Helper::escape($log['user_first'] || $log['user_last'] ? ($log['user_last'] . ' ' . $log['user_first']) : ($log['user_company'] ?: 'Système')) ?>
                                    </span>
                                    <small class="text-muted"><?= Helper::escape($log['user_email']) ?> (<?= Helper::escape($log['user_role']) ?>)</small>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="fw-bold d-block text-muted"><?= Helper::escape(Helper::entityLabel($log['entity_type'])) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php $details = Helper::auditMetadata($log['metadata']); ?>
                                <?php if (!empty($details)): ?>
                                    <div class="small text-muted" style="line-height:1.7;">
                                        <?php foreach ($details as $label => $val): ?>
                                            <div><strong><?= Helper::escape($label) ?> :</strong> <?= Helper::escape($val) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
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
