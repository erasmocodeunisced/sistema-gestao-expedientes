<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do utilizador | Gestão de Expedientes</title><link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-page">
    <header class="topbar"><a class="topbar-brand" href="dashboard.php"><span>GE</span> Gestão de Expedientes</a><a class="logout-button" href="users.php">Voltar à lista</a></header>
    <main class="content-shell narrow-shell"><p class="section-kicker">Administração</p><h1><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></h1><p class="muted">Informações da conta e permissões atribuídas através do papel.</p>
        <section class="detail-panel"><dl class="detail-data"><div><dt>Email</dt><dd><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Papel</dt><dd><?= htmlspecialchars($user['role_names'] ?: 'Sem papel', ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Estado</dt><dd><?= $user['is_active'] ? 'Ativo' : 'Inativo' ?></dd></div><div><dt>Criado em</dt><dd><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></dd></div></dl></section>
        <a class="primary-link" href="user-form.php?id=<?= (int) $user['id'] ?>">Editar utilizador</a>
    </main>
</body>
</html>