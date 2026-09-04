<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/UserController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'users.view');

$search = trim((string) ($_GET['search'] ?? ''));
$controller = new UserController();
$users = $controller->list($connection, $search);
$message = consumeFlashMessage();

require dirname(__DIR__) . '/app/views/users.php';