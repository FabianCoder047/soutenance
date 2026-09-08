<?php
declare(strict_types=1);
$isEdit = $isEdit ?? false;
$tasklistId = $tasklistId ?? '';
$existingQuestions = $existingQuestions ?? [];
$title = $isEdit ? 'Modifier la tasklist' : 'Nouvelle Tasklist';
$layout = Auth::user()['role'] === 'ADMIN' ? 'admin' : 'developer';
$questionsForJs = array_map(static function (array $q): array {
    return [
        'label' => $q['label'],
        'type' => $q['type'],
        'required' => (int)($q['required'] ?? 0) === 1,
        'options' => array_column($q['options'] ?? [], 'label'),
    ];
}, $existingQuestions);
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-premium p-4 p-md-5">
            <h5 class="fw-bold mb-4"><?= $isEdit ? 'Modifier la tasklist (brouillon)' : 'Créer une nouvelle tasklist' ?></h5>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= Helper::escape($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= $isEdit ? '/admin/tasklists/edit' : '/admin/tasklists/create' ?>" method="POST" class="form-premium" id="tasklistForm">
                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="tasklist_id" value="<?= Helper::escape($tasklistId) ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label for="title" class="form-label fw-semibold">Titre de la tasklist</label>
                    <input type="text" class="form-control" id="title" name="title" required value="<?= Helper::escape($title ?? '') ?>" placeholder="Ex: Informations à compléter pour le déploiement">
                </div>

                <div class="mb-4">
                    <label for="project_id" class="form-label fw-semibold">Projet concerné</label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="" disabled <?= empty($projectId) ? 'selected' : '' ?>>Sélectionner un projet...</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($projectId ?? '') === $proj['id'] ? 'selected' : '' ?>>
                                <?= Helper::escape($proj['name']) ?> (Client: <?= Helper::escape($proj['client_company'] ?? $proj['client_email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($layout === 'admin'): ?>
                    <div class="mb-4">
                        <label for="assigned_to_id" class="form-label fw-semibold">Assigner à</label>
                        <select class="form-select" id="assigned_to_id" name="assigned_to_id" required>
                            <?php foreach ($devs as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($assignedToId ?? '') === $d['id'] || (!$isEdit && Auth::user()['id'] === $d['id']) ? 'selected' : '' ?>>
                                    <?= Helper::escape($d['first_name'] . ' ' . $d['last_name']) ?> (<?= Helper::escape($d['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info border-0 rounded-3 p-3 mb-4">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Cette tasklist vous sera automatiquement assignée par défaut. L'administrateur pourra la réassigner si besoin.
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description / Consignes générales</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Contexte ou instructions complémentaires pour le client..."><?= Helper::escape($description ?? '') ?></textarea>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">Questions pour le client</h6>
                        <p class="text-muted small mb-0">Choix unique, choix multiples, texte long ou envoi de fichier.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary rounded-pill px-3" id="addQuestionBtn">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter une question
                    </button>
                </div>

                <div id="questionsContainer" class="d-flex flex-column gap-3 mb-4"></div>

                <div class="d-flex justify-content-end gap-3 mt-5">
                    <a href="<?= $isEdit ? '/tasklists/show?id=' . urlencode($tasklistId) : ($layout === 'admin' ? '/admin/tasklists' : '/developer/my-tasklists') ?>" class="btn btn-light rounded-pill px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5"><?= $isEdit ? 'Enregistrer les modifications' : 'Enregistrer en brouillon' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="questionTemplate">
    <div class="question-card border rounded-4 p-4 bg-white shadow-sm">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <h6 class="fw-bold mb-0">Question <span class="question-number"></span></h6>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill remove-question-btn">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Intitulé de la question</label>
                <input type="text" class="form-control question-label" required placeholder="Ex: Quel est le nom de domaine souhaité ?">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Type de réponse</label>
                <select class="form-select question-type">
                    <?php foreach (TasklistQuestion::TYPES as $typeKey => $typeLabel): ?>
                        <option value="<?= $typeKey ?>"><?= Helper::escape($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input question-required" type="checkbox" checked>
                    <label class="form-check-label">Question obligatoire</label>
                </div>
            </div>
        </div>

        <div class="options-block mt-3 d-none">
            <label class="form-label fw-semibold">Options de réponse</label>
            <div class="options-list d-flex flex-column gap-2"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill mt-2 add-option-btn">
                <i class="bi bi-plus-lg me-1"></i> Ajouter une option
            </button>
        </div>

        <div class="text-end mt-3">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 add-question-bottom-btn d-none">
                <i class="bi bi-plus-lg me-1"></i> Ajouter une question
            </button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate');
    const addBtn = document.getElementById('addQuestionBtn');
    const form = document.getElementById('tasklistForm');
    const existingQuestions = <?= json_encode($questionsForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let questionIndex = 0;

    function refreshQuestionNumbers() {
        const cards = container.querySelectorAll('.question-card');
        cards.forEach(function(card, index) {
            card.querySelector('.question-number').textContent = String(index + 1);
            card.querySelector('.add-question-bottom-btn').classList.toggle('d-none', index !== cards.length - 1);
        });
    }

    function createOptionRow(value) {
        const row = document.createElement('div');
        row.className = 'input-group';
        row.innerHTML = `
            <input type="text" class="form-control option-input" placeholder="Libellé de l'option" value="${value || ''}">
            <button type="button" class="btn btn-outline-danger remove-option-btn"><i class="bi bi-x-lg"></i></button>
        `;
        row.querySelector('.remove-option-btn').addEventListener('click', function() {
            row.remove();
        });
        return row;
    }

    function toggleOptions(card) {
        const type = card.querySelector('.question-type').value;
        const optionsBlock = card.querySelector('.options-block');
        const optionsList = card.querySelector('.options-list');
        const isChoice = type === 'SINGLE_CHOICE' || type === 'MULTIPLE_CHOICE';

        optionsBlock.classList.toggle('d-none', !isChoice);

        if (isChoice && optionsList.children.length === 0) {
            optionsList.appendChild(createOptionRow(''));
            optionsList.appendChild(createOptionRow(''));
        }
    }

    function wireQuestionCard(card) {
        card.querySelector('.remove-question-btn').addEventListener('click', function() {
            card.remove();
            refreshQuestionNumbers();
        });

        card.querySelector('.question-type').addEventListener('change', function() {
            toggleOptions(card);
        });

        card.querySelector('.add-option-btn').addEventListener('click', function() {
            card.querySelector('.options-list').appendChild(createOptionRow(''));
        });

        card.querySelector('.add-question-bottom-btn').addEventListener('click', function() {
            addQuestion();
        });
    }

    function addQuestion(data) {
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.question-card');
        container.appendChild(card);
        questionIndex++;
        refreshQuestionNumbers();
        wireQuestionCard(card);

        if (data) {
            card.querySelector('.question-label').value = data.label || '';
            card.querySelector('.question-type').value = data.type || 'LONG_TEXT';
            card.querySelector('.question-required').checked = !!data.required;
            toggleOptions(card);
            if (data.options && data.options.length) {
                const optionsList = card.querySelector('.options-list');
                optionsList.innerHTML = '';
                data.options.forEach(function(optionLabel) {
                    optionsList.appendChild(createOptionRow(optionLabel));
                });
            }
        } else {
            toggleOptions(card);
        }
    }

    addBtn.addEventListener('click', function() {
        addQuestion();
    });

    form.addEventListener('submit', function(event) {
        container.querySelectorAll('[data-generated-name]').forEach(function(el) {
            el.removeAttribute('name');
            el.removeAttribute('data-generated-name');
        });

        const cards = container.querySelectorAll('.question-card');
        if (cards.length === 0) {
            event.preventDefault();
            alert('Ajoutez au moins une question.');
            return;
        }

        cards.forEach(function(card, index) {
            const prefix = `questions[${index}]`;
            const labelInput = card.querySelector('.question-label');
            const typeSelect = card.querySelector('.question-type');
            const requiredCheckbox = card.querySelector('.question-required');

            labelInput.name = `${prefix}[label]`;
            labelInput.setAttribute('data-generated-name', '1');
            typeSelect.name = `${prefix}[type]`;
            typeSelect.setAttribute('data-generated-name', '1');

            if (requiredCheckbox.checked) {
                requiredCheckbox.name = `${prefix}[required]`;
                requiredCheckbox.value = '1';
                requiredCheckbox.setAttribute('data-generated-name', '1');
            } else {
                requiredCheckbox.removeAttribute('name');
            }

            card.querySelectorAll('.option-input').forEach(function(optionInput, optionIndex) {
                optionInput.name = `${prefix}[options][${optionIndex}]`;
                optionInput.setAttribute('data-generated-name', '1');
            });
        });
    });

    if (existingQuestions.length > 0) {
        existingQuestions.forEach(function(questionData) {
            addQuestion(questionData);
        });
    } else {
        addQuestion();
    }
});
</script>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/' . $layout . '.php';
?>
