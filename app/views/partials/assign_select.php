<?php
declare(strict_types=1);
// views/partials/assign_select.php
// Expects $tasklistId, $currentAssigneeId, $devsList
$tasklistId = $tasklistId ?? '';
$currentAssigneeId = $currentAssigneeId ?? '';
$devsList = $devsList ?? [];
?>
<form action="/admin/tasklists/assign" method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
    <input type="hidden" name="tasklist_id" value="<?= Helper::escape($tasklistId) ?>">
    <select name="assigned_to_id" class="form-select form-select-sm rounded-pill font-monospace" style="font-size: 0.8rem; width: 160px;" onchange="this.form.submit()">
        <?php foreach ($devsList as $dev): ?>
            <option value="<?= $dev['id'] ?>" <?= $currentAssigneeId === $dev['id'] ? 'selected' : '' ?>>
                <?= Helper::escape($dev['first_name'] . ' ' . $dev['last_name']) ?> (<?= Helper::escape($dev['role']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</form>
