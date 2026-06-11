<?php
declare(strict_types=1);
$title = 'Détail de la tasklist';
ob_start();
include dirname(__DIR__) . '/partials/tasklist-show.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/' . $layout . '.php';
