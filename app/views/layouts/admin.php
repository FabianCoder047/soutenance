<?php
declare(strict_types=1);
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$user = Auth::user();
$initials = strtoupper(substr($user['first_name'] ?? 'A', 0, 1) . substr($user['last_name'] ?? 'D', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::escape($title ?? 'Admin') ?> | e-Media Support</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="role-admin">

    <!-- Sidebar for Desktop -->
    <aside class="sidebar" id="sidebarMenu">
        <div class="brand d-flex align-items-center justify-content-between">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill text-warning"></i>
                e-Media Support
            </span>
            <button class="btn btn-close btn-close-white d-lg-none" onclick="toggleSidebar()"></button>
        </div>
        <nav class="sidebar-nav">
            <a href="/admin/dashboard" class="nav-link <?= $currentUri === '/admin/dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Tableau de bord
            </a>
            <a href="/admin/users" class="nav-link <?= str_starts_with($currentUri, '/admin/users') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
            <a href="/admin/projects" class="nav-link <?= str_starts_with($currentUri, '/admin/projects') ? 'active' : '' ?>">
                <i class="bi bi-folder-fill"></i> Projets
            </a>
            <a href="/admin/tasklists" class="nav-link <?= str_starts_with($currentUri, '/admin/tasklists') ? 'active' : '' ?>">
                <i class="bi bi-check2-square"></i> Tasklists
            </a>
            <a href="/admin/wishlists" class="nav-link <?= str_starts_with($currentUri, '/admin/wishlists') ? 'active' : '' ?>">
                <i class="bi bi-magic"></i> Wishlists
            </a>
            <a href="/admin/audit" class="nav-link <?= $currentUri === '/admin/audit' ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> Audit Log
            </a>
            <a href="/admin/profile" class="nav-link <?= $currentUri === '/admin/profile' ? 'active' : '' ?>">
                <i class="bi bi-person-fill-gear"></i> Mon Profil
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="/logout" class="nav-link text-danger w-100 p-2 d-flex align-items-center gap-2" style="background: none; border: none;">
                <i class="bi bi-box-arrow-left"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <h4 class="m-0 fw-bold text-dark"><?= Helper::escape($title ?? 'Table de bord') ?></h4>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <h6 class="m-0 fw-bold"><?= Helper::escape(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h6>
                    <small class="text-muted">Administrateur</small>
                </div>
                <div class="avatar-circle">
                    <?= Helper::escape($initials) ?>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="content-body">
            <!-- Flash messages -->
            <?php if (Session::has('success')): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 p-3 mb-4 animate-fade-in" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div><?= Session::get('success') ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php Session::remove('success'); ?>
                </div>
            <?php endif; ?>

            <?php if (Session::has('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 p-3 mb-4 animate-fade-in" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= Session::get('error') ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php Session::remove('error'); ?>
                </div>
            <?php endif; ?>

            <div class="animate-fade-in">
                <?= $content ?>
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/modal-portal.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            sidebar.classList.toggle('show');
        }
    </script>
</body>
</html>
