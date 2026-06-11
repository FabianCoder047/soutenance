<?php
declare(strict_types=1);
?>
<!-- Modal de confirmation : envoi tasklist au client -->
<div class="modal fade" id="publishTasklistModal" tabindex="-1" aria-labelledby="publishTasklistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-light">
                <h5 class="modal-title fw-bold text-primary" id="publishTasklistModalLabel">
                    <i class="bi bi-send me-2"></i>Envoyer au client
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="/tasklists/publish" method="POST" class="form-premium">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="tasklist_id" id="publish_tasklist_id" value="">
                    <p class="mb-2">
                        Vous allez publier la tasklist <strong id="publish_tasklist_title"></strong> :
                        le client pourra la consulter et y répondre.
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Cette action est irréversible pour le statut brouillon.
                    </p>
                </div>
                <div class="modal-footer border-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-send me-1"></i>Confirmer l'envoi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('publishTasklistModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;
        var id = button.getAttribute('data-tasklist-id') || '';
        var title = button.getAttribute('data-tasklist-title') || 'cette tasklist';
        document.getElementById('publish_tasklist_id').value = id;
        document.getElementById('publish_tasklist_title').textContent = title;
    });
});
</script>