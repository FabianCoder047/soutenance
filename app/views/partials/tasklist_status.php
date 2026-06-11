<?php
declare(strict_types=1);
$status = $status ?? 'PENDING';
?>
<span class="badge-custom tasklist-status-badge <?= Tasklist::statusClass($status) ?>">
    <?= Helper::escape(Tasklist::statusLabel($status)) ?>
</span>
