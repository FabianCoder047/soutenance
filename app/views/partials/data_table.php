<?php
declare(strict_types=1);
// views/partials/data_table.php
// Expects $headers (array of labels), $rowsHtml (pre-rendered rows markup)
$headers = $headers ?? [];
$rowsHtml = $rowsHtml ?? '';
?>
<div class="card-premium p-0">
    <div class="table-responsive">
        <table class="table table-premium m-0">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= Helper::escape($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?= $rowsHtml ?>
            </tbody>
        </table>
    </div>
</div>
