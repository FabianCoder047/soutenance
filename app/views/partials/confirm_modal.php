<?php
declare(strict_types=1);
// views/partials/confirm_modal.php
// Expects $modalId, $modalTitle, $modalText, $actionUrl, $actionName (e.g. "Archiver"), $confirmBtnClass (e.g. "btn-danger")
$modalId = $modalId ?? 'confirmModal';
$modalTitle = $modalTitle ?? 'Confirmation';
$modalText = $modalText ?? 'Êtes-vous sûr de vouloir effectuer cette action ?';
$actionUrl = $actionUrl ?? '#';
$actionName = $actionName ?? 'Confirmer';
$confirmBtnClass = $confirmBtnClass ?? 'btn-primary';
?>
<div class="modal fade" id="<?= Helper::escape($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-light">
                <h5 class="modal-title fw-bold"><?= Helper::escape($modalTitle) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= Helper::escape($actionUrl) ?>" method="POST" class="form-premium">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="id" id="<?= Helper::escape($modalId) ?>_id" value="">
                    <p id="<?= Helper::escape($modalId) ?>_text"><?= Helper::escape($modalText) ?></p>
                </div>
                <div class="modal-footer border-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn <?= Helper::escape($confirmBtnClass) ?> rounded-pill px-4"><?= Helper::escape($actionName) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
