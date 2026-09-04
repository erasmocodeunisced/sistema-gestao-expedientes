<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/DashboardController.php';

startSecureSession();

try {
    $connection = getDatabaseConnection();
    $user = getAuthenticatedUser($connection);
    $roles = getAuthenticatedRoles($connection);
    $permissions = getAuthenticatedPermissions($connection);
    $dashboardController = new DashboardController();
    $summary = $dashboardController->summary($connection);
    $recentActivity = $dashboardController->recentActivity($connection);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $user = false;
    $roles = [];
    $permissions = [];
}

if (!$user) {
    (new AuthController())->logout();
    header('Location: index.php');
    exit;
}

require dirname(__DIR__) . '/app/views/dashboard.php';