<?php

declare(strict_types=1);

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function getCsrfToken(): string
{
    startSecureSession();

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isAuthenticated(): bool
{
    startSecureSession();

    return isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) !== false;
}

function requireAuthentication(): void
{
    if (!isAuthenticated()) {
        header('Location: index.php');
        exit;
    }
}

function validateCsrfToken(?string $token): bool
{
    startSecureSession();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlashMessage(string $type, string $message): void
{
    startSecureSession();
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function consumeFlashMessage(): ?array
{
    startSecureSession();
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);

    return $message;
}