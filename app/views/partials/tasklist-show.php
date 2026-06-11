<?php
declare(strict_types=1);
$answers = $answers ?? [];
$questions = $questions ?? [];
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-premium p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?php $status = $tasklist['status']; include __DIR__ . '/tasklist_status.php'; ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= Helper::escape($tasklist['title']) ?></h4>
                    <p class="text-muted mb-0">Projet : <?= Helper::escape($tasklist['project_name']) ?></p>
                </div>
                <a href="<?= Helper::escape($backUrl) ?>" class="btn btn-light rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="border rounded-4 p-3 h-100">
                        <h6 class="fw-bold text-muted text-uppercase small mb-3">Informations</h6>
                        <dl class="mb-0 small">
                            <dt class="text-muted">Client</dt>
                            <dd class="mb-2"><?= Helper::escape($tasklist['client_company'] ?? '—') ?></dd>
                            <dt class="text-muted">Créée par</dt>
                            <dd class="mb-2"><?= Helper::escape(trim(($tasklist['creator_first'] ?? '') . ' ' . ($tasklist['creator_last'] ?? ''))) ?></dd>
                            <dt class="text-muted">Assignée à</dt>
                            <dd class="mb-2"><?= Helper::escape(trim(($tasklist['assignee_first'] ?? '') . ' ' . ($tasklist['assignee_last'] ?? ''))) ?: '—' ?></dd>
                            <dt class="text-muted">Créée le</dt>
                            <dd class="mb-0"><?= Helper::formatDateTime($tasklist['created_at']) ?></dd>
                            <?php if (!empty($tasklist['treated_at'])): ?>
                                <dt class="text-muted mt-2">Traitée le</dt>
                                <dd class="mb-0"><?= Helper::formatDateTime($tasklist['treated_at']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                <?php if (!empty($tasklist['description'])): ?>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h6 class="fw-bold text-muted text-uppercase small mb-3">Consignes</h6>
                            <div class="text-dark"><?= nl2br(Helper::escape($tasklist['description'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <h6 class="fw-bold mb-3">Questions</h6>
            <?php if (empty($questions)): ?>
                <p class="text-muted">Aucune question définie.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="border rounded-4 p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="fw-semibold"><?= ($index + 1) ?>. <?= Helper::escape($question['label']) ?></span>
                                <span class="badge bg-light text-dark border"><?= Helper::escape(TasklistQuestion::typeLabel($question['type'])) ?></span>
                            </div>
                            <?php if ((int)$question['required'] === 1): ?>
                                <small class="text-danger">Obligatoire</small>
                            <?php endif; ?>
                            <?php if (!empty($question['options'])): ?>
                                <ul class="small text-muted mb-0 mt-2 ps-3">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <li><?= Helper::escape($option['label']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($answers) || !empty($tasklist['client_response'])): ?>
                <h6 class="fw-bold mb-3 mt-4">Réponses du client</h6>
                <?php
                    $legacyResponse = $tasklist['client_response'] ?? '';
                    include __DIR__ . '/tasklist_answers.php';
                ?>
            <?php elseif (Tasklist::isDraft($tasklist)): ?>
                <div class="alert alert-warning border-0 rounded-3 mt-4 mb-0">
                    <i class="bi bi-eye-slash me-1"></i>
                    Cette tasklist est en brouillon : le client ne la voit pas tant qu'elle n'est pas envoyée.
                </div>
            <?php endif; ?>

            <div class="tasklist-actions-row mt-4 pt-3 border-top">
                <?php
                $t = $tasklist;
                $hideDetailsLink = true;
                include __DIR__ . '/tasklist_list_actions.php';
                ?>
            </div>
        </div>
    </div>
</div>

<?php if (($canPublish ?? false) || ($canDelete ?? false)): ?>
    <?php
    if ($canPublish ?? false) {
        include __DIR__ . '/publish_tasklist_modal.php';
    }
    if ($canDelete ?? false) {
        include __DIR__ . '/delete_tasklist_modal.php';
    }
    ?>
<?php endif; ?>
