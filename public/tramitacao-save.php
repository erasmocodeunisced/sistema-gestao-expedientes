<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/TramitacaoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'tramitacoes.manage');

$expeditionId = filter_var($_POST['expediente_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$redirect = $expeditionId ? 'tramitacoes.php?expediente_id=' . $expeditionId : 'expedientes.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $expeditionId === false || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: ' . $redirect);
    exit;
}

$responsibleUserId = filter_var($_POST['responsible_user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
try {
    if ($responsibleUserId === false) {
        throw new DomainException('O responsável selecionado é inválido.');
    }
    (new TramitacaoController())->create(
        $connection,
        $expeditionId,
        (int) $_SESSION['user_id'],
        trim((string) ($_POST['destination'] ?? '')),
        $responsibleUserId,
        (string) ($_POST['sent_at'] ?? ''),
        trim((string) ($_POST['observation'] ?? '')),
        (string) ($_POST['status'] ?? 'sent')
    );
    setFlashMessage('success', 'Tramitação registada com sucesso.');
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    setFlashMessage('error', 'Não foi possível registar a tramitação.');
}

header('Location: ' . $redirect);
exit;