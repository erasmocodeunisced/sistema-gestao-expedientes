<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'expedientes.view');

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$controller = new ExpeditionController();
$expeditions = $controller->list($connection, $search, $status);
$statuses = $controller->getStatuses();
$message = consumeFlashMessage();

require dirname(__DIR__) . '/app/views/expedientes.php';