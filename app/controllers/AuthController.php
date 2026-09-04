<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuditLogger.php';

final class AuthController
{
    public function authenticate(PDO $connection, string $email, string $password): bool
    {
        $statement = $connection->prepare(
            'SELECT id, password_hash FROM users WHERE email = :email AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            AuditLogger::record($connection, 'login_failed', 'authentication', null, null, ['email' => $email]);
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $update = $connection->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => $user['id']]);
        AuditLogger::record($connection, 'login', 'user', (int) $user['id']);

        return true;
    }

    public function logout(?PDO $connection = null): void
    {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        if ($connection !== null && $userId !== null) {
            AuditLogger::record($connection, 'logout', 'user', $userId);
        }
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }
}