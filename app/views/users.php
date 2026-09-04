<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizadores | Gestão de Expedientes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-page dashboard-layout">
    <?php $activePage = 'users'; $pageTitle = 'Utilizadores'; require __DIR__ . '/partials/app-header.php'; ?>

    <main class="content-shell">
        <div class="page-heading">
            <div><p class="section-kicker">Administração</p><h1>Utilizadores</h1><p class="muted">Consulte e mantenha as contas do sistema.</p></div>
            <a class="primary-link" href="user-form.php">Novo utilizador</a>
        </div>

        <?php if ($message): ?>
            <div class="flash <?= htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="search-bar" method="get" action="users.php">
            <label for="search">Pesquisar por nome ou email</label>
            <div><input id="search" name="search" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: nome@instituicao.org"><button type="submit">Pesquisar</button></div>
        </form>

        <section class="table-panel" aria-labelledby="users-title">
            <div class="panel-heading"><h2 id="users-title">Contas registadas</h2><span><?= count($users) ?> resultado(s)</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nome</th><th>Email</th><th>Papel</th><th>Estado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['role_names'] ?: 'Sem papel', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="status <?= $user['is_active'] ? 'active' : 'inactive' ?>"><?= $user['is_active'] ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="actions"><a href="user-view.php?id=<?= (int) $user['id'] ?>">Ver</a><a href="user-form.php?id=<?= (int) $user['id'] ?>">Editar</a><form method="post" action="user-toggle.php"><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"><button type="submit"><?= $user['is_active'] ? 'Desativar' : 'Ativar' ?></button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?><tr><td colspan="5" class="empty-state">Nenhum utilizador encontrado.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>