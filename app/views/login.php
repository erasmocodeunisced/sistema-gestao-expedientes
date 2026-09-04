<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Gestão de Expedientes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-intro">
            <p class="eyebrow">UnISCED · Faculdade de Engenharia e Agricultura</p>
            <h1>Gestão de Expedientes</h1>
            <p class="intro-copy">Acesso institucional para acompanhar o trabalho documental com clareza e segurança.</p>
        </section>

        <section class="auth-card" aria-labelledby="login-title">
            <div class="brand-mark" aria-hidden="true">GE</div>
            <p class="section-kicker">Área reservada</p>
            <h2 id="login-title">Iniciar sessão</h2>
            <p class="muted">Introduza as suas credenciais para continuar.</p>

            <?php if ($errorMessage !== null): ?>
                <div class="alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="index.php" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                <label for="password">Palavra-passe</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <button type="submit">Entrar</button>
            </form>
        </section>
    </main>
</body>
</html>