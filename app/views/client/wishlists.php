<?php
declare(strict_types=1);
$title = "Mes Demandes (Wishlist)";
ob_start();
?>

<!-- Header -->
<div class="card-premium p-4 mb-4">
    <div class="row align-items-center">
        <div class="col-8">
            <h5 class="m-0 fw-bold text-dark">Liste de mes attentes</h5>
        </div>
        <div class="col-4 text-end">
            <a href="/client/wishlists/new" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nouvelle Demande
            </a>
        </div>
    </div>
</div>

<!-- Wishlists List -->
<div class="card-premium p-0">
    <div class="table-responsive">
        <table class="table table-premium table-wishlists m-0">
            <thead>
                <tr>
                    <th class="col-demand">Demande</th>
                    <th>Projet lié</th>
                    <th>Statut</th>
                    <th>Date de création</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($wishlists)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Vous n'avez soumis aucune demande pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($wishlists as $w): ?>
                        <?php
                            $statusClass = 'badge-pending';
                            if ($w['status'] === 'ACKNOWLEDGED') $statusClass = 'badge-in_progress';
                            if ($w['status'] === 'DONE') $statusClass = 'badge-done';

                            $statusLabel = $w['status'];
                            if ($w['status'] === 'PENDING') $statusLabel = 'En attente';
                            if ($w['status'] === 'ACKNOWLEDGED') $statusLabel = 'Acquittée (Prise en compte)';
                            if ($w['status'] === 'DONE') $statusLabel = 'Traité (Fait)';
                        ?>
                        <tr>
                            <td class="col-demand">
                                <div class="wishlist-demand-text"><?= Helper::escape($w['content']) ?></div>
                            </td>
                            <td>
                                <span class="small fw-semibold text-dark"><?= Helper::escape($w['project_name'] ?: 'Général (Aucun projet)') ?></span>
                            </td>
                            <td>
                                <span class="badge-custom <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= Helper::formatDateTime($w['created_at']) ?></span>
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
