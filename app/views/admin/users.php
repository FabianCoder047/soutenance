<?php
declare(strict_types=1);
$title = "Gestion des Utilisateurs";
ob_start();
?>

<!-- Filter & Invite Header -->
<div class="card-premium p-4 mb-4">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <form action="/admin/users" method="GET" class="row g-2">
                <div class="col-auto">
                    <select name="role" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                        <option value="">Tous les rôles</option>
                        <option value="ADMIN" <?= ($filters['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>Administrateur</option>
                        <option value="DEV" <?= ($filters['role'] ?? '') === 'DEV' ? 'selected' : '' ?>>Développeur</option>
                        <option value="CLIENT" <?= ($filters['role'] ?? '') === 'CLIENT' ? 'selected' : '' ?>>Client</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select rounded-pill shadow-sm" style="font-size: 0.9rem;">
                        <option value="">Tous les statuts</option>
                        <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                        <option value="ACTIVE" <?= ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                        <option value="SUSPENDED" <?= ($filters['status'] ?? '') === 'SUSPENDED' ? 'selected' : '' ?>>Suspendu</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark rounded-pill px-3 shadow-sm"><i class="bi bi-filter"></i> Filtrer</button>
                    <?php if (!empty($filters['role']) || !empty($filters['status'])): ?>
                        <a href="/admin/users" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
                <i class="bi bi-person-plus-fill me-1"></i> Inviter un Utilisateur
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card-premium p-0">
    <div class="table-responsive">
        <table class="table table-premium m-0">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Date de création</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php 
                            $name = ($u['first_name'] || $u['last_name']) 
                                ? ($u['first_name'] . ' ' . $u['last_name']) 
                                : ($u['company_name'] ?: 'En attente...');
                            
                            $badgeClass = 'badge-pending';
                            $statusLabel = 'En attente';
                            if ($u['status'] === 'ACTIVE') {
                                $badgeClass = 'badge-done';
                                $statusLabel = 'Actif';
                            } else if ($u['status'] === 'SUSPENDED') {
                                $badgeClass = 'badge-suspended';
                                $statusLabel = 'Suspendu';
                            }

                            $init = '';
                            if ($u['role'] === 'CLIENT') {
                                $init = strtoupper(substr($u['company_name'] ?: 'CL', 0, 2));
                            } else {
                                $init = strtoupper(substr($u['first_name'] ?: 'U', 0, 1) . substr($u['last_name'] ?: '', 0, 1));
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        <?= Helper::escape($init) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= Helper::escape($name) ?></div>
                                        <small class="text-muted"><?= Helper::escape($u['role']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="small font-monospace"><?= Helper::escape($u['email']) ?></span></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= Helper::escape($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-custom <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td><span class="small text-muted"><?= Helper::formatDateTime($u['created_at']) ?></span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <?php if ($u['status'] === 'PENDING' && $u['invite_token']): ?>
                                        <!-- Helpful direct link copying for local testing -->
                                        <?php $link = Helper::appUrl('/invitation?token=' . urlencode((string)$u['invite_token'])); ?>
                                        <button class="btn btn-sm btn-outline-info rounded-pill px-2 py-1" 
                                                onclick="copyLink('<?= $link ?>')" 
                                                title="Copier le lien d'inscription pour test">
                                            <i class="bi bi-link-45deg"></i> Copier lien
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($u['id'] !== $user['id']): ?>
                                        <?php if ($u['status'] === 'ACTIVE'): ?>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#suspendModal" 
                                                    data-userid="<?= $u['id'] ?>" 
                                                    data-email="<?= Helper::escape($u['email']) ?>">
                                                Suspendre
                                            </button>
                                        <?php elseif ($u['status'] === 'SUSPENDED'): ?>
                                            <form action="/admin/users/status" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="status" value="ACTIVE">
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                    Réactiver
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
</div>

<!-- Invitation Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-light">
                <h5 class="modal-title fw-bold" id="inviteModalLabel">Inviter un nouvel utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/users/invite" method="POST" class="form-premium">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label for="invite_email" class="form-label fw-semibold">Adresse Email</label>
                        <input type="email" class="form-control" id="invite_email" name="email" required placeholder="nom@exemple.com">
                    </div>

                    <div class="mb-3">
                        <label for="invite_role" class="form-label fw-semibold">Rôle</label>
                        <select class="form-select" id="invite_role" name="role" required>
                            <option value="CLIENT" selected>Client (Accès Client)</option>
                            <option value="DEV">Développeur (Accès Technique)</option>
                            <option value="ADMIN">Administrateur (Gestion Totale)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Envoyer l'invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-light">
                <h5 class="modal-title fw-bold text-danger" id="suspendModalLabel">Suspendre le compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/users/status" method="POST" class="form-premium">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="user_id" id="suspend_user_id" value="">
                    <input type="hidden" name="status" value="SUSPENDED">
                    
                    <p>Êtes-vous sûr de vouloir suspendre le compte de <strong id="suspend_email"></strong> ? Cet utilisateur ne pourra plus se connecter.</p>
                    
                    <div class="mb-3">
                        <label for="suspend_reason" class="form-label fw-semibold">Raison (facultatif)</label>
                        <input type="text" class="form-control" id="suspend_reason" name="reason" placeholder="Ex: Départ de l'entreprise, impayés...">
                    </div>
                </div>
                <div class="modal-footer border-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Suspendre</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function copyLink(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Lien d\'invitation copié dans le presse-papiers :\n' + text);
        }, function(err) {
            alert('Impossible de copier le lien : ', err);
        });
    }

    // Modal listeners
    document.addEventListener('DOMContentLoaded', function() {
        const suspendModal = document.getElementById('suspendModal');
        if (suspendModal) {
            suspendModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-userid');
                const email = button.getAttribute('data-email');
                
                document.getElementById('suspend_user_id').value = userId;
                document.getElementById('suspend_email').textContent = email;
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/admin.php';
?>
