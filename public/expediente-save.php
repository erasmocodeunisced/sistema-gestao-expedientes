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
$hasExpeditionId = array_key_exists('id', $_POST);
$redirect = $expeditionId ? 'expediente-form.php?id=' . $expeditionId : 'expedientes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($hasExpeditionId && $expeditionId === false) || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: ' . $redirect);
    exit;
}

$data = [
    'reference_code' => trim((string) ($_POST['reference_code'] ?? '')),
    'subject' => trim((string) ($_POST['subject'] ?? '')),
    'description' => trim((string) ($_POST['description'] ?? '')),
    'document_type' => trim((string) ($_POST['document_type'] ?? '')),
    'origin' => trim((string) ($_POST['origin'] ?? '')),
    'destination' => trim((string) ($_POST['destination'] ?? '')),
    'priority' => (string) ($_POST['priority'] ?? ''),
    'status' => (string) ($_POST['status'] ?? ''),
    'received_at' => (string) ($_POST['received_at'] ?? ''),
];

try {
    $controller = new ExpeditionController();
    if ($expeditionId !== false && $expeditionId !== null) {
        $controller->update($connection, $expeditionId, $data, (int) $_SESSION['user_id']);
        setFlashMessage('success', 'Expediente atualizado com sucesso.');
    } else {
        $controller->create($connection, $data, (int) $_SESSION['user_id']);
        setFlashMessage('success', 'Expediente criado com sucesso.');
    }
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    setFlashMessage('error', 'Não foi possível guardar o expediente.');
}

header('Location: ' . $redirect);
exit;