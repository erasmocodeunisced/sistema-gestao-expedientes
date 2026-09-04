<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

function getAuthenticatedUser(PDO $connection): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    $statement = $connection->prepare(
        'SELECT id, full_name, email, is_active
         FROM users
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['id' => $_SESSION['user_id']]);
    $user = $statement->fetch();

    return $user ?: null;
}

function hasRole(PDO $connection, string $roleSlug): bool
{
    if (!isAuthenticated()) {
        return false;
    }

    $statement = $connection->prepare(
        'SELECT 1
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id
         INNER JOIN users u ON u.id = ur.user_id
         WHERE ur.user_id = :user_id
           AND r.slug = :role_slug
           AND r.is_active = 1
           AND u.is_active = 1
         LIMIT 1'
    );
    $statement->execute([
        'user_id' => $_SESSION['user_id'],
        'role_slug' => $roleSlug,
    ]);

    return (bool) $statement->fetchColumn();
}

function hasPermission(PDO $connection, string $permissionSlug): bool
{
    if (!isAuthenticated()) {
        return false;
    }

    $statement = $connection->prepare(
        'SELECT 1
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
         INNER JOIN role_permissions rp ON rp.role_id = r.id
         INNER JOIN permissions p ON p.id = rp.permission_id
         INNER JOIN users u ON u.id = ur.user_id
         WHERE ur.user_id = :user_id
           AND p.slug = :permission_slug
           AND u.is_active = 1
         LIMIT 1'
    );
    $statement->execute([
        'user_id' => $_SESSION['user_id'],
        'permission_slug' => $permissionSlug,
    ]);

    return (bool) $statement->fetchColumn();
}

function getAuthenticatedRoles(PDO $connection): array
{
    if (!isAuthenticated()) {
        return [];
    }

    $statement = $connection->prepare(
        'SELECT DISTINCT r.name
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id
         INNER JOIN users u ON u.id = ur.user_id
         WHERE ur.user_id = :user_id AND r.is_active = 1 AND u.is_active = 1
         ORDER BY r.id'
    );
    $statement->execute(['user_id' => $_SESSION['user_id']]);

    return array_column($statement->fetchAll(), 'name');
}

function getAuthenticatedPermissions(PDO $connection): array
{
    if (!isAuthenticated()) {
        return [];
    }

    $statement = $connection->prepare(
        'SELECT DISTINCT p.name, p.slug
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
         INNER JOIN role_permissions rp ON rp.role_id = r.id
         INNER JOIN permissions p ON p.id = rp.permission_id
         INNER JOIN users u ON u.id = ur.user_id
         WHERE ur.user_id = :user_id AND u.is_active = 1
         ORDER BY p.name'
    );
    $statement->execute(['user_id' => $_SESSION['user_id']]);

    return $statement->fetchAll();
}

function requirePermission(PDO $connection, string $permissionSlug): void
{
    requireAuthentication();

    if (!hasPermission($connection, $permissionSlug)) {
        http_response_code(403);
        require dirname(__DIR__) . '/views/access-denied.php';
        exit;
    }
}