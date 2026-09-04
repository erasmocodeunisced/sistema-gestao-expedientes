<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';
require_once dirname(__DIR__) . '/app/controllers/TramitacaoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'tramitacoes.view');

$expeditionId = filter_var($_GET['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$expeditionController = new ExpeditionController();
$tramitacaoController = new TramitacaoController();
$expeditions = $expeditionController->list($connection);
$selectedExpedition = $expeditionId ? $expeditionController->find($connection, $expeditionId) : null;

if ($expeditionId && !$selectedExpedition) {
    http_response_code(404);
    require dirname(__DIR__) . '/app/views/access-denied.php';
    exit;
}

$history = $selectedExpedition ? $tramitacaoController->history($connection, $expeditionId) : [];
$statuses = $tramitacaoController->getStatuses();
$message = consumeFlashMessage();

require dirname(__DIR__) . '/app/views/tramitacoes.php';