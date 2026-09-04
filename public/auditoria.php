<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/AuditoriaController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'auditoria.view');

$search = trim((string) ($_GET['search'] ?? ''));
$userId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = trim((string) ($_GET['action'] ?? ''));
$entityType = trim((string) ($_GET['entity_type'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$controller = new AuditoriaController();
$result = $controller->list($connection, $search, $userId ?: null, $action, $entityType, $dateFrom, $dateTo, $page);
$users = $controller->users($connection);
$actions = $controller->actions($connection);
$entities = $controller->entities($connection);
$query = $_GET;
unset($query['page']);

require dirname(__DIR__) . '/app/views/auditoria.php';