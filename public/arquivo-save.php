<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ArquivoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'arquivos.manage');
$expeditionId = filter_var($_POST['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$redirect = $expeditionId ? 'expediente-view.php?id=' . $expeditionId : 'arquivo.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $expeditionId === false || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: ' . $redirect);
    exit;
}
try {
    (new ArquivoController())->archive(
        $connection,
        $expeditionId,
        (int) $_SESSION['user_id'],
        trim((string) ($_POST['archive_reference'] ?? '')),
        trim((string) ($_POST['notes'] ?? ''))
    );
    setFlashMessage('success', 'Expediente arquivado com sucesso.');
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    setFlashMessage('error', 'Não foi possível arquivar o expediente.');
}
header('Location: ' . $redirect);
exit;