<?php
declare(strict_types=1);
$title = "Gestion des Wishlists Client";
ob_start();
?>

<!-- Filter Header -->
<div class="card-premium p-4 mb-4">
    <form action="/admin/wishlists" method="GET" class="row g-2 align-items-center">
        <div class="col-auto">
            <select name="project_id" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                <option value="">Tous les projets</option>
                <?php foreach ($projects as $proj): ?>
                    <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] ?? '') === $proj['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($proj['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="status" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                <option value="">Tous les statuts</option>
                <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                <option value="ACKNOWLEDGED" <?= ($filters['status'] ?? '') === 'ACKNOWLEDGED' ? 'selected' : '' ?>>Acquittée</option>
                <option value="DONE" <?= ($filters['status'] ?? '') === 'DONE' ? 'selected' : '' ?>>Faite</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm"><i class="bi bi-filter"></i> Filtrer</button>
            <?php if (!empty($filters['project_id']) || !empty($filters['status'])): ?>
                <a href="/admin/wishlists" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Wishlists List -->
<div class="card-premium p-0">
    <div class="table-responsive">
        <table class="table table-premium table-wishlists m-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th class="col-demand">Demandes</th>
                    <th>Projet lié</th>
                    <th>Statut</th>
                    <th>Date d'envoi</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($wishlists)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Aucune demande en attente.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($wishlists as $w): ?>
                        <?php
                            $statusClass = 'badge-pending';
                            if ($w['status'] === 'ACKNOWLEDGED') $statusClass = 'badge-in_progress';
                            if ($w['status'] === 'DONE') $statusClass = 'badge-done';

                            $statusLabel = $w['status'];
                            if ($w['status'] === 'PENDING') $statusLabel = 'En attente';
                            if ($w['status'] === 'ACKNOWLEDGED') $statusLabel = 'Acquittée';
                            if ($w['status'] === 'DONE') $statusLabel = 'Fait';
                        ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-dark d-block"><?= Helper::escape($w['client_company']) ?></span>
                                <small class="text-muted"><?= Helper::escape($w['client_email']) ?></small>
                            </td>
                            <td class="col-demand">
                                <div class="wishlist-demand-text"><?= Helper::escape($w['content']) ?></div>
                            </td>
                            <td>
                                <span class="small fw-semibold"><?= Helper::escape($w['project_name'] ?: 'Général (Aucun projet)') ?></span>
                            </td>
                            <td>
                                <span class="badge-custom <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= Helper::formatDateTime($w['created_at']) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <?php if ($w['status'] === 'PENDING'): ?>
                                        <form action="/admin/wishlists/status" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                            <input type="hidden" name="wishlist_id" value="<?= $w['id'] ?>">
                                            <input type="hidden" name="status" value="ACKNOWLEDGED">
                                            <button type="submit" class="btn btn-sm btn-outline-info rounded-pill px-3">Acquitter</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($w['status'] !== 'DONE'): ?>
                                        <form action="/admin/wishlists/status" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                            <input type="hidden" name="wishlist_id" value="<?= $w['id'] ?>">
                                            <input type="hidden" name="status" value="DONE">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3"><i class="bi bi-check-lg"></i> Marquer Fait</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> Terminé</span>
                                    <?php endif; ?>
                                </div>
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
include dirname(__DIR__) . '/layouts/admin.php';
?>
