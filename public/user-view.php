<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/UserController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'users.view');

$userId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$user = $userId ? (new UserController())->find($connection, $userId) : null;

if (!$user) {
    http_response_code(404);
    require dirname(__DIR__) . '/app/views/access-denied.php';
    exit;
}

require dirname(__DIR__) . '/app/views/user-view.php';