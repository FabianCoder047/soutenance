<?php
declare(strict_types=1);
$title = "Gestion des Projets";
ob_start();
?>

<!-- Filter & Actions Header -->
<div class="card-premium p-4 mb-4">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <form action="/admin/projects" method="GET" class="row g-2">
                <div class="col-auto">
                    <select name="client_id" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                        <option value="">Tous les clients</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($filters['client_id'] ?? '') === $c['id'] ? 'selected' : '' ?>>
                                <?= Helper::escape($c['company_name'] ?? $c['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                        <option value="">Tous les statuts</option>
                        <option value="ACTIVE" <?= ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                        <option value="ARCHIVED" <?= ($filters['status'] ?? '') === 'ARCHIVED' ? 'selected' : '' ?>>Archivé</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm"><i class="bi bi-filter"></i> Filtrer</button>
                    <?php if (!empty($filters['client_id']) || !empty($filters['status'])): ?>
                        <a href="/admin/projects" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="/admin/projects/create" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-folder-plus me-1"></i> Nouveau Projet
            </a>
        </div>
    </div>
</div>

<!-- Projects list -->
<div class="card-premium p-0">
    <div class="table-responsive">
        <table class="table table-premium m-0">
            <thead>
                <tr>
                    <th>Projet</th>
                    <th>Client</th>
                    <th>Créé par</th>
                    <th>Statut</th>
                    <th>Date de création</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Aucun projet trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <?php
                            $statusClass = $p['status'] === 'ACTIVE' ? 'badge-done' : 'badge-suspended';
                            $statusLabel = $p['status'] === 'ACTIVE' ? 'Actif' : 'Archivé';
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="fw-bold text-dark mb-1"><?= Helper::escape($p['name']) ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;">
                                        <?= Helper::escape($p['description'] ?: 'Aucune description') ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= Helper::escape($p['client_company'] ?? $p['client_email']) ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= Helper::escape(($p['creator_first'] ?? '') . ' ' . ($p['creator_last'] ?? '')) ?></span>
                            </td>
                            <td>
                                <span class="badge-custom <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= Helper::formatDateTime($p['created_at']) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="/admin/tasklists?project_id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-list-task me-1"></i> Tasklists
                                    </a>
                                    <a href="/admin/projects/edit?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                        <i class="bi bi-pencil-fill me-1" style="font-size: 0.8rem;"></i> Modifier
                                    </a>
                                    
                                    <?php if ($p['status'] === 'ACTIVE'): ?>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#archiveModal" 
                                                data-id="<?= $p['id'] ?>" 
                                                data-name="<?= Helper::escape($p['name']) ?>">
                                            Archiver
                                        </button>
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

<!-- Archive Project Confirmation Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-light">
                <h5 class="modal-title fw-bold text-danger" id="archiveModalLabel">Archiver le projet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/projects/archive" method="POST" class="form-premium">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="id" id="archive_project_id" value="">
                    <p>Êtes-vous sûr de vouloir archiver le projet <strong id="archive_project_name"></strong> ? Les clients et développeurs ne pourront plus ajouter de tasklists sur ce projet.</p>
                </div>
                <div class="modal-footer border-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Archiver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const archiveModal = document.getElementById('archiveModal');
        if (archiveModal) {
            archiveModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                
                document.getElementById('archive_project_id').value = id;
                document.getElementById('archive_project_name').textContent = name;
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
?>
