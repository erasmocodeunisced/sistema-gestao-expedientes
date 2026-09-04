<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';
require_once dirname(__DIR__) . '/app/controllers/TramitacaoController.php';
require_once dirname(__DIR__) . '/app/controllers/DespachoController.php';
require_once dirname(__DIR__) . '/app/controllers/ArquivoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'arquivos.view');
$archiveId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$archive = $archiveId ? (new ArquivoController())->findById($connection, $archiveId) : null;
if (!$archive) {
    http_response_code(404);
    require dirname(__DIR__) . '/app/views/access-denied.php';
    exit;
}
$expedition = (new ExpeditionController())->find($connection, (int) $archive['expediente_id']);
$tramitacoes = (new TramitacaoController())->history($connection, (int) $archive['expediente_id']);
$despachos = (new DespachoController())->history($connection, (int) $archive['expediente_id']);
$message = consumeFlashMessage();
require dirname(__DIR__) . '/app/views/arquivo-view.php';