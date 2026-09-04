<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/UserController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'users.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Não foi possível validar o pedido.');
    header('Location: users.php');
    exit;
}

$controller = new UserController();
$userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$hasUserId = array_key_exists('id', $_POST);
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

try {
    if ($hasUserId && $userId === false) {
        throw new DomainException('O utilizador selecionado é inválido.');
    }
    if ($roleId === false) {
        throw new DomainException('O papel selecionado é inválido.');
    }

    if ($userId !== false && $userId !== null) {
        $controller->update($connection, $userId, (int) $_SESSION['user_id'], $fullName, $email, $password, $roleId);
        setFlashMessage('success', 'Utilizador atualizado com sucesso.');
    } else {
        $controller->create($connection, $fullName, $email, $password, $roleId, (int) $_SESSION['user_id']);
        setFlashMessage('success', 'Utilizador criado com sucesso.');
    }
} catch (DomainException $exception) {
    setFlashMessage('error', $exception->getMessage());
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    setFlashMessage('error', 'Não foi possível guardar o utilizador.');
}

header('Location: ' . ($userId ? 'user-form.php?id=' . $userId : 'users.php'));
exit;