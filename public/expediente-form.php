<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'expedientes.manage');

$controller = new ExpeditionController();
$expeditionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$expedition = $expeditionId ? $controller->find($connection, $expeditionId) : null;

if ($expeditionId && !$expedition) {
    setFlashMessage('error', 'Expediente não encontrado.');
    header('Location: expedientes.php');
    exit;
}

$statuses = $controller->getStatuses();
$message = consumeFlashMessage();

require dirname(__DIR__) . '/app/views/expediente-form.php';