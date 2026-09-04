<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $user ? 'Editar utilizador' : 'Novo utilizador' ?> | Gestão de Expedientes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-page">
    <header class="topbar"><a class="topbar-brand" href="dashboard.php"><span>GE</span> Gestão de Expedientes</a><a class="logout-button" href="users.php">Voltar à lista</a></header>
    <main class="content-shell narrow-shell">
        <p class="section-kicker">Administração</p>
        <h1><?= $user ? 'Editar utilizador' : 'Novo utilizador' ?></h1>
        <p class="muted">Os dados são validados antes de serem guardados.</p>
        <?php if ($message): ?><div class="flash <?= htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert"><?= htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form class="user-form" method="post" action="user-save.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($user): ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><?php endif; ?>
            <label for="full_name">Nome completo</label><input id="full_name" name="full_name" required value="<?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <label for="email">Email</label><input id="email" name="email" type="email" required value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <label for="password">Palavra-passe <?= $user ? '(deixe vazio para manter)' : '' ?></label><input id="password" name="password" type="password" <?= $user ? '' : 'required' ?> autocomplete="new-password">
            <p class="field-help">Mínimo de 8 caracteres, com maiúscula, minúscula e número.</p>
            <label for="role_id">Papel</label><select id="role_id" name="role_id" required><option value="">Selecione um papel</option><?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>" <?= $selectedRoleId === (int) $role['id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
            <button class="form-submit" type="submit"><?= $user ? 'Guardar alterações' : 'Criar utilizador' ?></button>
        </form>
    </main>
</body>
</html>