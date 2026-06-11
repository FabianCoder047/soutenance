<?php
declare(strict_types=1);
// views/partials/status_badge.php
// Expects $status variable to be set
$status = $status ?? 'PENDING';
$class = 'badge-pending';
$label = $status;

switch ($status) {
    case 'ACTIVE':
        $class = 'badge-active';
        $label = 'Actif';
        break;
    case 'SUSPENDED':
        $class = 'badge-suspended';
        $label = 'Suspendu';
        break;
    case 'DRAFT':
        $class = 'badge-draft';
        $label = 'Brouillon';
        break;
    case 'PENDING':
        $class = 'badge-pending';
        $label = 'En attente';
        break;
    case 'IN_PROGRESS':
        $class = 'badge-in_progress';
        $label = 'En cours';
        break;
    case 'CLIENT_FILLED':
        $class = 'badge-client_filled';
        $label = 'Réponse Client';
        break;
    case 'DONE':
        $class = 'badge-done';
        $label = 'Traité';
        break;
    case 'ACKNOWLEDGED':
        $class = 'badge-in_progress';
        $label = 'Acquittée';
        break;
}
?>
<span class="badge-custom <?= $class ?>"><?= Helper::escape($label) ?></span>
