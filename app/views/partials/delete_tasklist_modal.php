<?php
declare(strict_types=1);
?>
<div class="modal fade" id="deleteTasklistModal" tabindex="-1" aria-labelledby="deleteTasklistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-light">
                <h5 class="modal-title fw-bold text-danger" id="deleteTasklistModalLabel">
                    <i class="bi bi-trash me-2"></i>Supprimer la tasklist
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="/tasklists/delete" method="POST" class="form-premium">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="tasklist_id" id="delete_tasklist_id" value="">
                    <p class="mb-2">
                        Confirmez-vous la suppression de <strong id="delete_tasklist_title"></strong> ?
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Cette action est définitive (questions, réponses et fichiers associés).
                    </p>
                </div>
                <div class="modal-footer border-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                        <i class="bi bi-trash me-1"></i>Supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('deleteTasklistModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;
        document.getElementById('delete_tasklist_id').value = button.getAttribute('data-tasklist-id') || '';
        document.getElementById('delete_tasklist_title').textContent = button.getAttribute('data-tasklist-title') || 'cette tasklist';
    });
});
</script>
