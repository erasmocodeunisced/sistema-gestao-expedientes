<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/middleware/authorization.php';

startSecureSession();
$connection = getDatabaseConnection();
requirePermission($connection, 'rbac.manage');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Administrativa | Gestão de Expedientes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-page">
    <main class="dashboard-shell">
        <p class="section-kicker">Demonstração RBAC</p>
        <h1>Área administrativa autorizada</h1>
        <p class="muted">Esta área exige a permissão <strong>rbac.manage</strong>, consultada diretamente na base de dados.</p>
        <a class="back-link" href="dashboard.php">Voltar ao dashboard</a>
    </main>
</body>
</html>