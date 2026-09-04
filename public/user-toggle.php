<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/UserController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'users.manage');

$userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === false || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: users.php');
    exit;
}

try {
    $active = (new UserController())->toggleStatus($connection, $userId, (int) $_SESSION['user_id']);
    setFlashMessage('success', $active ? 'Utilizador ativado com sucesso.' : 'Utilizador desativado com sucesso.');
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
}

header('Location: users.php');
exit;