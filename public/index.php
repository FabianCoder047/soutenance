<?php
declare(strict_types=1);

// Error Reporting Config
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load environment variables from .env
if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Strip quotes if present
            if (str_starts_with($val, '"') && str_ends_with($val, '"')) {
                $val = substr($val, 1, -1);
            }
            $_ENV[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

// Start PHP Session
require_once dirname(__DIR__) . '/app/core/Session.php';
Session::start();

// Include classes
require_once dirname(__DIR__) . '/app/core/Router.php';
require_once dirname(__DIR__) . '/app/core/Helper.php';
require_once dirname(__DIR__) . '/app/core/Auth.php';
require_once dirname(__DIR__) . '/app/core/Database.php';

// Include controllers
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/InvitationController.php';
require_once dirname(__DIR__) . '/app/controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/controllers/DeveloperController.php';
require_once dirname(__DIR__) . '/app/controllers/ClientController.php';
require_once dirname(__DIR__) . '/app/controllers/ProjectController.php';
require_once dirname(__DIR__) . '/app/controllers/TasklistController.php';
require_once dirname(__DIR__) . '/app/controllers/WishlistController.php';
require_once dirname(__DIR__) . '/app/controllers/AuditController.php';

// Define Routes using static Router methods
// Auth Routes
Router::get('/', 'AuthController@login');

Router::get('/login', 'AuthController@login');
Router::post('/login', 'AuthController@login');
Router::get('/logout', 'AuthController@logout');
Router::get('/reset-password', 'AuthController@resetRequest');
Router::post('/reset-password', 'AuthController@resetRequest');
Router::get('/reset-password/confirm', 'AuthController@resetConfirm');
Router::post('/reset-password/confirm', 'AuthController@resetConfirm');
Router::get('/2fa', 'AuthController@verify2fa');
Router::post('/2fa', 'AuthController@verify2fa');
Router::get('/invitation', 'InvitationController@complete');
Router::post('/invitation', 'InvitationController@complete');

// Admin Routes
Router::get('/admin/dashboard', 'AdminController@dashboard');
Router::get('/admin/users', 'AdminController@users');
Router::post('/admin/users/invite', 'AdminController@inviteUser');
Router::post('/admin/users/status', 'AdminController@updateStatus');
Router::get('/admin/profile', 'AdminController@profile');
Router::post('/admin/profile', 'AdminController@profile');

Router::get('/admin/projects', 'ProjectController@indexAdmin');
Router::get('/admin/projects/create', 'ProjectController@create');
Router::post('/admin/projects/create', 'ProjectController@create');
Router::get('/admin/projects/edit', 'ProjectController@edit');
Router::post('/admin/projects/edit', 'ProjectController@edit');
Router::post('/admin/projects/archive', 'ProjectController@archive');

Router::get('/admin/tasklists', 'TasklistController@indexAdmin');
Router::get('/admin/tasklists/create', 'TasklistController@create');
Router::post('/admin/tasklists/create', 'TasklistController@create');
Router::get('/admin/tasklists/edit', 'TasklistController@edit');
Router::post('/admin/tasklists/edit', 'TasklistController@edit');
Router::post('/admin/tasklists/assign', 'TasklistController@assign');
Router::post('/admin/tasklists/treat', 'TasklistController@treat');
Router::get('/tasklists/show', 'TasklistController@show');
Router::post('/tasklists/publish', 'TasklistController@publish');
Router::post('/tasklists/delete', 'TasklistController@delete');

Router::get('/admin/wishlists', 'WishlistController@indexAdmin');
Router::post('/admin/wishlists/status', 'WishlistController@updateStatus');

Router::get('/admin/audit', 'AuditController@index');

// Developer Routes
Router::get('/developer/dashboard', 'DeveloperController@dashboard');
Router::get('/developer/my-projects', 'ProjectController@indexDev');
Router::get('/developer/my-tasklists', 'TasklistController@indexDev');
Router::get('/developer/projects', 'ProjectController@indexDevAll');
Router::get('/developer/profile', 'DeveloperController@profile');
Router::post('/developer/profile', 'DeveloperController@profile');

// Client Routes
Router::get('/client/dashboard', 'ClientController@dashboard');
Router::get('/client/projects', 'ProjectController@indexClient');
Router::get('/client/tasklists', 'TasklistController@indexClient');
Router::get('/client/tasklists/fill', 'TasklistController@clientFillForm');
Router::post('/client/tasklists/fill', 'TasklistController@clientFill');
Router::get('/tasklists/files/download', 'TasklistController@downloadFile');
Router::get('/client/wishlists', 'WishlistController@indexClient');
Router::get('/client/wishlists/new', 'WishlistController@create');
Router::post('/client/wishlists/new', 'WishlistController@create');
Router::get('/client/profile', 'ClientController@profile');
Router::post('/client/profile', 'ClientController@profile');

// Dispatch Router with current method and URI
Router::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
