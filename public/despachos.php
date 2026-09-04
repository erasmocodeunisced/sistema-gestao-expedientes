<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/DespachoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'despachos.view');
$search = trim((string) ($_GET['search'] ?? ''));
$expeditionId = filter_var($_GET['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$controller = new DespachoController();
$dispatches = $controller->list($connection, $search, $expeditionId ?: null);
$statuses = $controller->statuses();
$message = consumeFlashMessage();
require dirname(__DIR__) . '/app/views/despachos.php';