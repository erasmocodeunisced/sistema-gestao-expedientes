<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/DespachoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'despachos.manage');
$expeditionId = filter_var($_POST['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$redirect = $expeditionId ? 'despachos.php?expediente_id=' . $expeditionId : 'despachos.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $expeditionId === false || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: ' . $redirect);
    exit;
}
try {
    (new DespachoController())->create(
        $connection,
        $expeditionId,
        (int) $_SESSION['user_id'],
        trim((string) ($_POST['content'] ?? '')),
        trim((string) ($_POST['decision'] ?? '')),
        trim((string) ($_POST['observation'] ?? '')),
        (string) ($_POST['created_at'] ?? ''),
        (string) ($_POST['status'] ?? 'registered')
    );
    setFlashMessage('success', 'Despacho registado com sucesso.');
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    setFlashMessage('error', 'Não foi possível registar o despacho.');
}
header('Location: ' . $redirect);
exit;