<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/DespachoController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'despachos.view');
$dispatchId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$dispatch = $dispatchId ? (new DespachoController())->find($connection, $dispatchId) : null;
if (!$dispatch) {
    http_response_code(404);
    require dirname(__DIR__) . '/app/views/access-denied.php';
    exit;
}
$statuses = (new DespachoController())->statuses();
require dirname(__DIR__) . '/app/views/despacho-view.php';