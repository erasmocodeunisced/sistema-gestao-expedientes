<aside class="sidebar" id="main-sidebar">
    <div class="sidebar-brand"><span class="brand-symbol">GE</span><div><strong>Gestão de</strong><small>Expedientes</small></div></div>
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
        <?php if (hasPermission($connection, 'config.view')): ?><a class="nav-item <?= ($activePage ?? '') === 'config' ? 'active' : '' ?>" href="config.php"><span class="nav-icon">⚙</span>Configurações</a><?php endif; ?>
    </nav>
</aside>