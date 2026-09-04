<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';
require_once dirname(__DIR__) . '/app/controllers/TramitacaoController.php';
require_once dirname(__DIR__) . '/app/controllers/DespachoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'expedientes.view');

$expeditionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$expedition = $expeditionId ? (new ExpeditionController())->find($connection, $expeditionId) : null;

if (!$expedition) {
    http_response_code(404);
    require dirname(__DIR__) . '/app/views/access-denied.php';
    exit;
}

$message = consumeFlashMessage();
$tramitacoes = (new TramitacaoController())->history($connection, $expeditionId);
$despachos = (new DespachoController())->history($connection, $expeditionId);
require dirname(__DIR__) . '/app/views/expediente-view.php';