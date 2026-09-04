<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ExpeditionController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'expedientes.manage');

$expeditionId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = (string) ($_POST['status'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $expeditionId === false || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: expedientes.php');
    exit;
}

try {
    (new ExpeditionController())->changeStatus($connection, $expeditionId, $status, (int) $_SESSION['user_id']);
    setFlashMessage('success', 'Estado do expediente atualizado com sucesso.');
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
}

header('Location: expediente-view.php?id=' . $expeditionId);
exit;