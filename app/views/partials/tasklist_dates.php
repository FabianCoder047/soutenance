<?php
declare(strict_types=1);
$row = $t ?? $tasklist ?? null;
if ($row === null) {
    return;
}

$createdAt = $row['created_at'] ?? null;
$treatedAt = $row['treated_at'] ?? null;
?>
<div class="tasklist-dates">
    <?php if (!empty($createdAt)): ?>
        <div class="tasklist-date-item">
            <span class="tasklist-date-label"><i class="bi bi-calendar3 me-1"></i>Créée</span>
            <time class="tasklist-date-value" datetime="<?= Helper::escape(Helper::datetimeIso($createdAt)) ?>">
                <span class="tasklist-date-day"><?= Helper::escape(Helper::formatDate($createdAt)) ?></span>
                <span class="tasklist-date-time"><?= Helper::escape(Helper::formatTime($createdAt)) ?></span>
            </time>
        </div>
    <?php endif; ?>

    <?php if (!empty($treatedAt)): ?>
        <div class="tasklist-date-item tasklist-date-item--treated">
            <span class="tasklist-date-label"><i class="bi bi-check-circle me-1"></i>Traitée</span>
            <time class="tasklist-date-value" datetime="<?= Helper::escape(Helper::datetimeIso($treatedAt)) ?>">
                <span class="tasklist-date-day"><?= Helper::escape(Helper::formatDate($treatedAt)) ?></span>
                <span class="tasklist-date-time"><?= Helper::escape(Helper::formatTime($treatedAt)) ?></span>
            </time>
        </div>
    <?php endif; ?>
</div>
