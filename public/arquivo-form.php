<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';
require_once dirname(__DIR__) . '/app/controllers/ArquivoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'arquivos.manage');
$expeditionId = filter_var($_GET['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$expedition = $expeditionId ? (new ExpeditionController())->find($connection, $expeditionId) : null;
if (!$expedition) {
    setFlashMessage('error', 'Expediente não encontrado.');
    header('Location: expedientes.php');
    exit;
}
if ((new ArquivoController())->findByExpedition($connection, $expeditionId)) {
    setFlashMessage('error', 'Este expediente já está arquivado.');
    header('Location: expediente-view.php?id=' . $expeditionId);
    exit;
}
if ($expedition['status'] !== 'completed') {
    setFlashMessage('error', 'Só é possível arquivar expedientes concluídos.');
    header('Location: expediente-view.php?id=' . $expeditionId);
    exit;
}
$message = consumeFlashMessage();
require dirname(__DIR__) . '/app/views/arquivo-form.php';