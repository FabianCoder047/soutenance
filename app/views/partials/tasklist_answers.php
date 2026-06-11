<?php
declare(strict_types=1);

$answers = $answers ?? [];
$compact = $compact ?? false;
?>

<?php if (!empty($answers)): ?>
    <div class="bg-light border-start border-4 border-success p-3 rounded-3 my-2" style="font-size: 0.85rem;">
        <div class="text-success fw-bold mb-2">
            <i class="bi bi-chat-left-text-fill me-1"></i> Réponses client
        </div>
        <?php foreach ($answers as $answer): ?>
            <div class="<?= $compact ? 'mb-2' : 'mb-3' ?>">
                <div class="fw-semibold text-dark"><?= Helper::escape($answer['question_label']) ?></div>
                <small class="text-muted d-block mb-1"><?= Helper::escape(TasklistQuestion::typeLabel($answer['question_type'])) ?></small>

                <?php if ($answer['question_type'] === 'LONG_TEXT'): ?>
                    <div class="text-dark"><?= nl2br(Helper::escape($answer['text_value'] ?? '')) ?></div>

                <?php elseif (in_array($answer['question_type'], ['SINGLE_CHOICE', 'MULTIPLE_CHOICE'], true)): ?>
                    <?php if (!empty($answer['selected_options'])): ?>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($answer['selected_options'] as $option): ?>
                                <li><?= Helper::escape($option['label']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-muted">Aucune option sélectionnée</span>
                    <?php endif; ?>

                <?php elseif ($answer['question_type'] === 'FILE_UPLOAD'): ?>
                    <?php if (!empty($answer['files'])): ?>
                        <ul class="mb-0 ps-0 list-unstyled">
                            <?php foreach ($answer['files'] as $file): ?>
                                <li class="mb-1">
                                    <a href="/tasklists/files/download?id=<?= urlencode($file['id']) ?>" class="text-decoration-none">
                                        <i class="bi bi-paperclip me-1"></i>
                                        <?= Helper::escape($file['original_name']) ?>
                                    </a>
                                    <small class="text-muted">(<?= number_format(((int)$file['file_size']) / 1024, 1) ?> Ko)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-muted">Aucun fichier</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif (!empty($legacyResponse)): ?>
    <div class="bg-light border-start border-4 border-success p-2 rounded-3 my-2" style="font-size: 0.85rem;">
        <span class="text-success fw-bold"><i class="bi bi-chat-left-text-fill me-1"></i> Réponse client :</span>
        <span class="text-dark"><?= Helper::escape($legacyResponse) ?></span>
    </div>
<?php endif; ?>
