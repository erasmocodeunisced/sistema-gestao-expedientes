<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';
require_once dirname(__DIR__) . '/app/controllers/RelatorioController.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'relatorios.view');
$controller = new RelatorioController();

try {
    $filters = $controller->normalizeFilters(
        $_GET['date_from'] ?? null,
        $_GET['date_to'] ?? null,
        $_GET['status'] ?? '',
        $_GET['user_id'] ?? null,
        $_GET['destination'] ?? null
    );
    $data = [
        'summary' => $controller->summary($connection, $filters),
        'by_status' => $controller->byStatus($connection, $filters),
        'by_period' => $controller->byPeriod($connection, $filters),
        'archived' => $controller->archived($connection, $filters),
        'tramitacoes' => $controller->tramitacoes($connection, $filters),
        'despachos' => $controller->despachos($connection, $filters),
        'user_activity' => $controller->userActivity($connection, $filters),
        'audit_events' => $controller->auditEvents($connection, $filters),
    ];
} catch (DomainException $exception) {
    $filters = ['from' => '', 'to' => '', 'status' => '', 'user_id' => null, 'destination' => ''];
    $data = null;
    $errorMessage = $exception->getMessage();
}

if (($_GET['format'] ?? '') === 'csv' && $data !== null) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorios.csv"');
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['RELATÓRIOS DO SISTEMA'], ';');
    fputcsv($output, ['Filtros', csvSafe(json_encode($filters, JSON_UNESCAPED_UNICODE))], ';');
    fputcsv($output, [] , ';');
    fputcsv($output, ['RESUMO', 'TOTAL'], ';');
    foreach ($data['summary'] as $key => $value) { fputcsv($output, [csvSafe($key), csvSafe($value)], ';'); }
    foreach ([['EXPEDIENTES POR ESTADO', $data['by_status']], ['EXPEDIENTES POR PERÍODO', $data['by_period']], ['EXPEDIENTES ARQUIVADOS', $data['archived']], ['TRAMITAÇÕES', $data['tramitacoes']], ['DESPACHOS', $data['despachos']], ['ATIVIDADE DOS UTILIZADORES', $data['user_activity']], ['EVENTOS DE AUDITORIA', $data['audit_events']]] as [$title, $rows]) {
        fputcsv($output, [], ';');
        fputcsv($output, [$title], ';');
        if ($rows) { fputcsv($output, array_map('csvSafe', array_keys($rows[0])), ';'); foreach ($rows as $row) { fputcsv($output, array_map('csvSafe', $row), ';'); } }
    }
    fclose($output);
    exit;
}

function csvSafe(mixed $value): mixed
{
    if (!is_string($value) || $value === '') {
        return $value;
    }

    return in_array($value[0], ['=', '+', '-', '@'], true) ? "'" . $value : $value;
}

$users = $controller->users($connection);
$statuses = $controller->statuses();
$query = $_GET;
$query['format'] = 'csv';
$csvUrl = 'relatorios.php?' . http_build_query($query);
require dirname(__DIR__) . '/app/views/relatorios.php';