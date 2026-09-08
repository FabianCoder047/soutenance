<?php
declare(strict_types=1);
/** @var array $pagination Paramètres de pagination (Helper::paginationFromRequest) */
/** @var int|null $total Nombre total d'enregistrements */

if (empty($pagination) || !is_array($pagination)) {
    return;
}

$page = (int)($pagination['page'] ?? 1);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 25);
$perPageOptions = $pagination['per_page_options'] ?? [10, 25, 50, 100];
$total = (int)($total ?? 0);

if ($total <= 0) {
    return;
}

$offset = ($page - 1) * $perPage;
$from = $offset + 1;
$to = min($offset + $perPage, $total);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = $_GET;
unset($base['page'], $base['per_page']);

$pageUrl = static function (int $p) use ($path, $base, $perPage): string {
    $q = $base;
    $q['per_page'] = $perPage;
    if ($p > 1) {
        $q['page'] = $p;
    }
    return $path . '?' . http_build_query($q);
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3 px-2">
    <div class="text-muted small">
        Affichage de <strong><?= $from ?></strong> à <strong><?= $to ?></strong> sur <strong><?= $total ?></strong> élément<?= $total > 1 ? 's' : '' ?>
    </div>

    <form method="GET" class="d-flex align-items-center gap-2 m-0">
        <?php foreach ($base as $key => $val): ?>
            <?php if ($val === '' || $val === null || is_array($val)): continue; endif; ?>
            <input type="hidden" name="<?= Helper::escape((string)$key) ?>" value="<?= Helper::escape((string)$val) ?>">
        <?php endforeach; ?>
        <label class="small text-muted mb-0">Éléments par page</label>
        <select name="per_page" class="form-select form-select-sm rounded-pill shadow-sm" style="width: auto;" onchange="this.form.submit()">
            <?php foreach ($perPageOptions as $n): ?>
                <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-2">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pageUrl($page - 1) ?>">Précédent</a>
            </li>
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($p = $startPage; $p <= $endPage; $p++):
            ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $pageUrl($p) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pageUrl($page + 1) ?>">Suivant</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>