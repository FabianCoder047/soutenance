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
            'date' => $_GET['date'] ?? ''
        ];

        $logs = AuditLog::getAll($filters);
        
        // Fetch all users to populate the filter dropdown
        $users = User::getAll();

        include dirname(__DIR__) . '/views/admin/audit.php';
    }
}
