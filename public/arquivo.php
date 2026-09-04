<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/ArquivoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'arquivos.view');
$search = trim((string) ($_GET['search'] ?? ''));
$controller = new ArquivoController();
$archives = $controller->list($connection, $search);
$message = consumeFlashMessage();
require dirname(__DIR__) . '/app/views/arquivo.php';