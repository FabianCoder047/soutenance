<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/models/AuditLog.php';
require_once dirname(__DIR__) . '/models/User.php';

class AuditController {
    public function index(): void {
        Auth::require('ADMIN');
        $user = Auth::user();

        $filters = [
            'action' => $_GET['action'] ?? '',
            'user_id' => $_GET['user_id'] ?? '',
            'entity_type' => $_GET['entity_type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int)($_GET['per_page'] ?? 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = AuditLog::countAll($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $filters['limit'] = $perPage;
        $filters['offset'] = $offset;

        $logs = AuditLog::getAll($filters);

        // Fetch all users to populate the filter dropdown
        $users = User::getAll();

        include dirname(__DIR__) . '/views/admin/audit.php';
    }
}
