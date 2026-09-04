<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/UserController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'users.manage');

$controller = new UserController();
$userId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$user = $userId ? $controller->find($connection, $userId) : null;

if ($userId && !$user) {
    setFlashMessage('error', 'Utilizador não encontrado.');
    header('Location: users.php');
    exit;
}

$roles = $controller->getRoles($connection);
$selectedRoleId = $userId ? $controller->getAssignedRoleId($connection, $userId) : null;
$message = consumeFlashMessage();

require dirname(__DIR__) . '/app/views/user-form.php';