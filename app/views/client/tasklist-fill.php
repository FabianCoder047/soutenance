<?php
declare(strict_types=1);
$title = "Répondre à la tasklist";
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-premium p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><?= Helper::escape($tasklist['title']) ?></h5>
                    <p class="text-muted mb-0">Projet : <?= Helper::escape($tasklist['project_name']) ?></p>
                </div>
                <a href="/client/tasklists" class="btn btn-light rounded-pill px-3">Retour</a>
            </div>

            <?php if (!empty($tasklist['description'])): ?>
                <div class="alert alert-info border-0 rounded-3 mb-4">
                    <?= nl2br(Helper::escape($tasklist['description'])) ?>
                </div>
            <?php endif; ?>

            <form action="/client/tasklists/fill" method="POST" enctype="multipart/form-data" class="form-premium">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <input type="hidden" name="tasklist_id" value="<?= Helper::escape($tasklist['id']) ?>">

                <?php foreach ($questions as $index => $question): ?>
                    <?php
                        $questionId = $question['id'];
                        $existing = $answersByQuestion[$questionId] ?? null;
                        $isRequired = true;
                    ?>
                    <div class="mb-4 pb-4 border-bottom">
                        <label class="form-label fw-semibold d-block">
                            <?= ($index + 1) ?>. <?= Helper::escape($question['label']) ?>
                            <?php if ($isRequired): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <small class="text-muted d-block mb-3"><?= Helper::escape(TasklistQuestion::typeLabel($question['type'])) ?></small>

                        <?php if ($question['type'] === 'LONG_TEXT'): ?>
                            <textarea
                                class="form-control"
                                name="answers[<?= Helper::escape($questionId) ?>]"
                                rows="5"
                                <?= $isRequired ? 'required' : '' ?>
                                placeholder="Saisissez votre réponse ici..."><?= Helper::escape($existing['text_value'] ?? '') ?></textarea>

                        <?php elseif ($question['type'] === 'SINGLE_CHOICE'): ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <?php
                                    $checked = false;
                                    if (!empty($existing['selected_options'])) {
                                        foreach ($existing['selected_options'] as $selected) {
                                            if ($selected['id'] === $option['id']) {
                                                $checked = true;
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="answers[<?= Helper::escape($questionId) ?>]"
                                        id="q_<?= Helper::escape($questionId) ?>_<?= Helper::escape($option['id']) ?>"
                                        value="<?= Helper::escape($option['id']) ?>"
                                        <?= $checked ? 'checked' : '' ?>
                                        <?= $isRequired ? 'required' : '' ?>>
                                    <label class="form-check-label" for="q_<?= Helper::escape($questionId) ?>_<?= Helper::escape($option['id']) ?>">
                                        <?= Helper::escape($option['label']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>

                        <?php elseif ($question['type'] === 'MULTIPLE_CHOICE'): ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <?php
                                    $checked = false;
                                    if (!empty($existing['selected_options'])) {
                                        foreach ($existing['selected_options'] as $selected) {
                                            if ($selected['id'] === $option['id']) {
                                                $checked = true;
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="answers[<?= Helper::escape($questionId) ?>][]"
                                        id="q_<?= Helper::escape($questionId) ?>_<?= Helper::escape($option['id']) ?>"
                                        value="<?= Helper::escape($option['id']) ?>"
                                        <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="q_<?= Helper::escape($questionId) ?>_<?= Helper::escape($option['id']) ?>">
                                        <?= Helper::escape($option['label']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>

                        <?php elseif ($question['type'] === 'FILE_UPLOAD'): ?>
                            <?php if (!empty($existing['files'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted d-block mb-1">Fichier(s) déjà envoyé(s) :</small>
                                    <?php foreach ($existing['files'] as $file): ?>
                                        <div>
                                            <a href="/tasklists/files/download?id=<?= urlencode($file['id']) ?>">
                                                <?= Helper::escape($file['original_name']) ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <input
                                type="file"
                                class="form-control"
                                name="files[<?= Helper::escape($questionId) ?>]"
                                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                <?= ($isRequired && empty($existing['files'])) ? 'required' : '' ?>>
                            <small class="text-muted">Images ou documents — max. 10 Mo (jpg, png, pdf, doc, xls, txt…)</small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-end gap-3">
                    <a href="/client/tasklists" class="btn btn-light rounded-pill px-4">Annuler</a>
                    <button type="submit" class="btn btn-warning rounded-pill px-5 text-dark fw-bold">Valider mes réponses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/client.php';
?>
