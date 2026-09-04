<?php
$currentUser = getAuthenticatedUser($connection);
$currentRoles = getAuthenticatedRoles($connection);
$pageTitle = $pageTitle ?? 'Gestão de Expedientes';
?>
<aside class="sidebar" id="main-sidebar">
    <div class="sidebar-brand"><a href="dashboard.php" class="brand-symbol">GE</a><div><strong>Gestão de</strong><small>Expedientes</small></div></div>
    <nav class="sidebar-nav" aria-label="Navegação principal">
        <p class="nav-label">Área de trabalho</p>
        <a class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>" href="dashboard.php"><span class="nav-icon">⌂</span>Dashboard</a>
        <?php if (hasPermission($connection, 'expedientes.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'expedientes' ? 'active' : '' ?>" href="expedientes.php"><span class="nav-icon">▤</span>Expedientes</a><?php endif; ?>
        <?php if (hasPermission($connection, 'tramitacoes.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'tramitacoes' ? 'active' : '' ?>" href="tramitacoes.php"><span class="nav-icon">↗</span>Tramitação</a><?php endif; ?>
        <?php if (hasPermission($connection, 'despachos.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'despachos' ? 'active' : '' ?>" href="despachos.php"><span class="nav-icon">✓</span>Despachos</a><?php endif; ?>
        <?php if (hasPermission($connection, 'arquivos.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'arquivo' ? 'active' : '' ?>" href="arquivo.php"><span class="nav-icon">▣</span>Arquivo</a><?php endif; ?>
        <p class="nav-label nav-label-spaced">Administração</p>
        <?php if (hasPermission($connection, 'users.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>" href="users.php"><span class="nav-icon">♙</span>Utilizadores</a><?php endif; ?>
        <?php if (hasPermission($connection, 'auditoria.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'auditoria' ? 'active' : '' ?>" href="auditoria.php"><span class="nav-icon">◷</span>Auditoria</a><?php endif; ?>
        <?php if (hasPermission($connection, 'relatorios.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'relatorios' ? 'active' : '' ?>" href="relatorios.php"><span class="nav-icon">▥</span>Relatórios</a><?php endif; ?>
        <?php if (hasPermission($connection, 'rbac.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'config' ? 'active' : '' ?>" href="users.php#permissoes"><span class="nav-icon">⚙</span>Configurações</a><?php endif; ?>
    </nav>
</aside>
<script>
    (function () {
        const toggle = document.querySelector('.menu-toggle');
        const sidebar = document.getElementById('main-sidebar');
        if (!toggle || !sidebar) return;
        toggle.addEventListener('click', function () {
            const expanded = sidebar.classList.toggle('open');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 760 && sidebar.classList.contains('open') && !sidebar.contains(event.target) && event.target !== toggle) {
                sidebar.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }());
</script>
<header class="app-topbar">
        <button class="menu-toggle" type="button" aria-controls="main-sidebar" aria-expanded="false" aria-label="Abrir menu">☰</button>
        <div class="breadcrumb"><span>Área de trabalho</span><strong><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="user-menu">
            <div class="user-avatar"><?= htmlspecialchars(strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-meta"><strong><?= htmlspecialchars($currentUser['full_name'] ?? 'Utilizador', ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars(implode(', ', $currentRoles) ?: 'Sem papel', ENT_QUOTES, 'UTF-8') ?></span></div>
            <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"><button class="logout-button" type="submit">Sair</button></form>
        </div>
</header>
