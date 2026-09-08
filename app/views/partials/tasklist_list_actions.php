<?php
declare(strict_types=1);
/** @var array $t */
$row = $t ?? $tasklist ?? null;
if ($row === null) {
    return;
}

$user = Auth::user();
$layoutRole = $user['role'] ?? '';
$showUrl = '/tasklists/show?id=' . urlencode($row['id']);
$isDraft = ($row['status'] ?? '') === 'DRAFT';
$status = $row['status'] ?? '';

$canEdit = Tasklist::canEdit($user, $row);
$canDelete = Tasklist::canDelete($user, $row);
$canPublish = $canEdit;

$canTreat = in_array($layoutRole, ['ADMIN', 'DEV'], true)
    && $status !== 'DONE'
    && !Tasklist::isDraft($row)
    && ($layoutRole === 'ADMIN' || ($row['assigned_to_id'] ?? '') === $user['id'])
    && Tasklist::hasClientResponded($row);

$canRespond = Tasklist::canClientRespond($user, $row);
$clientHasResponded = $layoutRole === 'CLIENT' && Tasklist::hasClientResponded($row);

$hideDetailsLink = $hideDetailsLink ?? false;
?>

<div class="tasklist-actions">
    <?php if (!$hideDetailsLink): ?>
        <a href="<?= $showUrl ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap">
            <i class="bi bi-eye"></i> Détails
        </a>
    <?php endif; ?>

    <?php if ($canEdit): ?>
        <a href="/admin/tasklists/edit?id=<?= urlencode($row['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap">
            <i class="bi bi-pencil"></i> Modifier
        </a>
    <?php endif; ?>

    <?php if ($canPublish): ?>
        <button type="button"
                class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap"
                data-bs-toggle="modal"
                data-bs-target="#publishTasklistModal"
                data-tasklist-id="<?= Helper::escape($row['id']) ?>"
                data-tasklist-title="<?= Helper::escape($row['title']) ?>">
            <i class="bi bi-send"></i> Envoyer
        </button>
    <?php endif; ?>

    <?php if ($canTreat): ?>
        <form action="/admin/tasklists/treat" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
            <input type="hidden" name="tasklist_id" value="<?= Helper::escape($row['id']) ?>">
            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 text-nowrap">
                <i class="bi bi-check-lg"></i> Traiter
            </button>
        </form>
    <?php elseif (in_array($layoutRole, ['ADMIN', 'DEV'], true) && $status === 'DONE'): ?>
        <span class="text-success small fw-bold px-1"><i class="bi bi-check-all"></i></span>
    <?php endif; ?>

    <?php if ($canRespond): ?>
        <a href="/client/tasklists/fill?id=<?= urlencode($row['id']) ?>" class="btn btn-sm btn-warning rounded-pill px-3 text-nowrap">
            <i class="bi bi-pencil-square"></i> Répondre
        </a>
    <?php elseif ($layoutRole === 'CLIENT' && $status === 'DONE'): ?>
        <span class="text-success small fw-bold px-1 text-nowrap"><i class="bi bi-check-all"></i> Traitée</span>
    <?php elseif ($clientHasResponded): ?>
        <span class="text-success small fw-bold px-1 text-nowrap"><i class="bi bi-check-circle"></i> Répondu</span>
    <?php endif; ?>

    <?php if ($canDelete): ?>
        <button type="button"
                class="btn btn-sm btn-outline-danger rounded-pill px-3 text-nowrap"
                data-bs-toggle="modal"
                data-bs-target="#deleteTasklistModal"
                data-tasklist-id="<?= Helper::escape($row['id']) ?>"
                data-tasklist-title="<?= Helper::escape($row['title']) ?>">
            <i class="bi bi-trash"></i> Supprimer
        </button>
    <?php endif; ?>
</div>
