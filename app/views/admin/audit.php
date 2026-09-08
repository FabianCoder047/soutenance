<?php
declare(strict_types=1);
$title = "Journal d'Audit";
ob_start();

$actions = Helper::auditActions();
$entities = Helper::auditEntities();

$relativeTime = static function (string $dt): string {
    $ts = strtotime($dt);
    if ($ts === false) {
        return $dt;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return "à l'instant";
    }
    if ($diff < 3600) {
        return "il y a " . floor($diff / 60) . " min";
    }
    if ($diff < 86400) {
        return "il y a " . floor($diff / 3600) . " h";
    }
    if ($diff < 2592000) {
        return "il y a " . floor($diff / 86400) . " j";
    }
    return Helper::formatDateTime($dt);
};

$auditActionClass = static function (string $action): string {
    if (in_array($action, [
        'PROJECT_CREATED', 'TASKLIST_CREATED', 'TASKLIST_PUBLISHED',
        'TASKLIST_CLIENT_FILLED', 'USER_INVITED', 'USER_REACTIVATED',
        'PASSWORD_RESET_COMPLETED', 'OTP_VERIFIED', 'WISHLIST_CREATED',
    ], true)) {
        return 'audit-badge--create';
    }
    if (in_array($action, ['TASKLIST_UPDATED', 'TASKLIST_ASSIGNED', 'TASKLIST_TREATED', 'PASSWORD_RESET_REQUESTED', 'OTP_SENT'], true)) {
        return 'audit-badge--update';
    }
    if (in_array($action, ['PROJECT_ARCHIVED', 'TASKLIST_DELETED', 'USER_SUSPENDED', 'OTP_FAILED'], true)) {
        return 'audit-badge--danger';
    }
    return 'audit-badge--auth';
};

$entityIcon = static function (string $entity): string {
    return match ($entity) {
        'Project' => 'bi-briefcase',
        'Tasklist' => 'bi-list-check',
        'User' => 'bi-person-badge',
        'Wishlist' => 'bi-inbox',
        default => 'bi-archive',
    };
};

$roleLabel = static function (string $role): string {
    return match ($role) {
        'ADMIN' => 'Administrateur',
        'DEV' => 'Développeur',
        'CLIENT' => 'Client',
        default => $role,
    };
};

$initials = static function (array $log): string {
    $first = mb_substr(trim((string)($log['user_first'] ?? '')), 0, 1);
    $last = mb_substr(trim((string)($log['user_last'] ?? '')), 0, 1);
    if ($first !== '' || $last !== '') {
        return mb_strtoupper($first . $last);
    }
    return 'SYS';
};
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

        <div class="col-12 d-flex flex-wrap justify-content-md-end align-items-center gap-3 mt-3 mt-md-0">
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted fw-bold mb-0">Logs par page</label>
                <select name="per_page" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem; width: auto;" onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $n): ?>
                        <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="audit-entity-icon" style="width: 42px; height: 42px; font-size: 1.1rem;"><i class="bi bi-clock-history"></i></span>
            <div>
                <h6 class="fw-bold mb-0">Historique des activités</h6>
                <small class="text-muted"><?= $total ?> événement<?= $total > 1 ? 's' : '' ?> enregistré<?= $total > 1 ? 's' : '' ?></small>
            </div>
        </div>
        <?php if (!empty($logs)): ?>
            <div class="text-muted small">
                Affichage de <strong><?= $offset + 1 ?></strong> à <strong><?= min($offset + $perPage, $total) ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($logs)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox d-block mb-3" style="font-size: 2.2rem; opacity: 0.4;"></i>
            Aucun enregistrement d'audit ne correspond aux critères.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-premium m-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Événement</th>
                        <th>Utilisateur</th>
                        <th>Entité</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <div class="audit-date">
                                    <span class="audit-date-relative"><?= Helper::escape($relativeTime($log['created_at'])) ?></span>
                                    <span class="audit-date-full"><?= Helper::formatDateTime($log['created_at']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="audit-badge <?= $auditActionClass($log['action']) ?>">
                                    <?= Helper::escape(Helper::actionLabel($log['action'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="audit-user-cell">
                                    <span class="avatar-circle sm"><?= Helper::escape($initials($log)) ?></span>
                                    <div>
                                        <span class="fw-semibold text-dark d-block">
                                            <?= Helper::escape($log['user_first'] || $log['user_last'] ? (trim($log['user_last'] . ' ' . $log['user_first'])) : ($log['user_company'] ?: 'Système')) ?>
                                        </span>
                                        <small class="text-muted"><?= Helper::escape($log['user_email']) ?> · <?= Helper::escape($roleLabel($log['user_role'])) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="audit-entity-cell">
                                    <span class="audit-entity-icon"><i class="bi <?= $entityIcon($log['entity_type']) ?>"></i></span>
                                    <?= Helper::escape(Helper::entityLabel($log['entity_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php $details = Helper::auditMetadata($log['metadata']); ?>
                                <?php if (!empty($details)): ?>
                                    <div class="audit-meta">
                                        <?php foreach ($details as $label => $val): ?>
                                            <div class="audit-meta-item">
                                                <span class="audit-meta-label"><?= Helper::escape($label) ?> :</span>
                                                <span class="audit-meta-value"><?= Helper::escape($val) ?></span>
                                            </div>
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

        <?php
        $baseQuery = $_GET;
        unset($baseQuery['page']);
        $pageUrl = static function (int $p) use ($baseQuery): string {
            $q = $baseQuery;
            $q['page'] = $p;
            return '/admin/audit?' . http_build_query($q);
        };
        ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
            <div class="text-muted small">
                Affichage de <strong><?= $offset + 1 ?></strong> à <strong><?= min($offset + $perPage, $total) ?></strong> sur <strong><?= $total ?></strong> logs
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-2">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $pageUrl($page - 1) ?>">Précédent</a>
                    </li>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $pageUrl($p) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $pageUrl($page + 1) ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
