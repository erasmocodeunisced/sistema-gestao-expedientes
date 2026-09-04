<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';
require_once dirname(__DIR__) . '/app/controllers/DespachoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'despachos.manage');
$expeditionId = filter_var($_GET['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$expedition = $expeditionId ? (new ExpeditionController())->find($connection, $expeditionId) : null;
if (!$expedition) {
    setFlashMessage('error', 'Expediente não encontrado.');
    header('Location: expedientes.php');
    exit;
}
$statuses = (new DespachoController())->statuses();
$message = consumeFlashMessage();
require dirname(__DIR__) . '/app/views/despacho-form.php';