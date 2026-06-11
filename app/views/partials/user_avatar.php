<?php
declare(strict_types=1);
// views/partials/user_avatar.php
// Expects $avatarUser (user array) and optional $size ('lg' or empty)
$avatarUser = $avatarUser ?? [];
$sizeClass = ($size ?? '') === 'lg' ? 'lg' : '';

$initials = 'U';
if (($avatarUser['role'] ?? '') === 'CLIENT') {
    $initials = strtoupper(substr($avatarUser['company_name'] ?? 'CL', 0, 2));
} else {
    $first = substr($avatarUser['first_name'] ?? 'U', 0, 1);
    $last = substr($avatarUser['last_name'] ?? '', 0, 1);
    $initials = strtoupper($first . $last);
}
?>
<div class="avatar-circle <?= $sizeClass ?>">
    <?= Helper::escape($initials) ?>
</div>
