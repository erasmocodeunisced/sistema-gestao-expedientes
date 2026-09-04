<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/middleware/session.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? null)) {
    try {
        $connection = getDatabaseConnection();
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $connection = null;
    }
    (new AuthController())->logout($connection);
}

header('Location: index.php');
exit;