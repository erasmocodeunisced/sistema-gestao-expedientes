<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuditLogger.php';

final class UserController
{
    public function list(PDO $connection, string $search = ''): array
    {
        $statement = $connection->prepare(
            'SELECT u.id, u.full_name, u.email, u.is_active, u.created_at,
                    GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ", ") AS role_names
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.full_name LIKE :search_name OR u.email LIKE :search_email
             GROUP BY u.id, u.full_name, u.email, u.is_active, u.created_at
             ORDER BY u.full_name'
        );
        $term = '%' . $search . '%';
        $statement->execute(['search_name' => $term, 'search_email' => $term]);

        return $statement->fetchAll();
    }

    public function find(PDO $connection, int $id): ?array
    {
        $statement = $connection->prepare(
            'SELECT u.id, u.full_name, u.email, u.is_active, u.created_at, u.updated_at,
                    GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ", ") AS role_names
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = :id
             GROUP BY u.id, u.full_name, u.email, u.is_active, u.created_at, u.updated_at'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function getRoles(PDO $connection): array
    {
        return $connection->query(
            'SELECT id, name, slug FROM roles WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
    }

    public function getAssignedRoleId(PDO $connection, int $userId): ?int
    {
        $statement = $connection->prepare(
            'SELECT role_id FROM user_roles WHERE user_id = :user_id ORDER BY role_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $roleId = $statement->fetchColumn();

        return $roleId === false ? null : (int) $roleId;
    }

    public function create(PDO $connection, string $fullName, string $email, string $password, int $roleId, ?int $actorId = null): int
    {
        $this->validateInput($connection, $fullName, $email, $password, $roleId);

        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)'
            );
            $statement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $connection->lastInsertId();
            $this->assignRole($connection, $userId, $roleId);
            AuditLogger::record($connection, 'user_created', 'user', $userId, null, ['full_name' => $fullName, 'email' => $email, 'role_id' => $roleId], $actorId);
            $connection->commit();

            return $userId;
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function update(PDO $connection, int $userId, int $actorId, string $fullName, string $email, string $password, int $roleId): void
    {
        if ($userId === $actorId && $roleId !== $this->getAssignedRoleId($connection, $userId)) {
            throw new DomainException('Não pode alterar o próprio papel.');
        }

        $this->validateInput($connection, $fullName, $email, $password, $roleId, $userId, false);
        $before = $this->find($connection, $userId);
        $connection->beginTransaction();

        try {
            $fields = ['full_name = :full_name', 'email = :email'];
            $parameters = ['full_name' => $fullName, 'email' => $email, 'id' => $userId];

            if ($password !== '') {
                $fields[] = 'password_hash = :password_hash';
                $parameters['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $statement = $connection->prepare(
                'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id'
            );
            $statement->execute($parameters);
            $this->assignRole($connection, $userId, $roleId);
            AuditLogger::record($connection, 'user_updated', 'user', $userId, ['full_name' => $before['full_name'], 'email' => $before['email']], ['full_name' => $fullName, 'email' => $email, 'role_id' => $roleId], $actorId);
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function toggleStatus(PDO $connection, int $userId, int $actorId): bool
    {
        if ($userId === $actorId) {
            throw new DomainException('Não pode desativar a própria conta.');
        }

        $user = $this->find($connection, $userId);
        if (!$user) {
            throw new DomainException('Utilizador não encontrado.');
        }

        $statement = $connection->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $newStatus = $user['is_active'] ? 0 : 1;
        $statement->execute(['is_active' => $newStatus, 'id' => $userId]);
        AuditLogger::record($connection, $newStatus ? 'user_activated' : 'user_deactivated', 'user', $userId, ['is_active' => (int) $user['is_active']], ['is_active' => $newStatus], $actorId);

        return (bool) $newStatus;
    }

    private function assignRole(PDO $connection, int $userId, int $roleId): void
    {
        $statement = $connection->prepare(
            'SELECT id FROM roles WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $roleId]);
        if (!$statement->fetchColumn()) {
            throw new DomainException('O papel selecionado é inválido.');
        }

        $delete = $connection->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);
        $insert = $connection->prepare(
            'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
        );
        $insert->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }

    private function validateInput(PDO $connection, string $fullName, string $email, string $password, int $roleId, ?int $exceptUserId = null, bool $passwordRequired = true): void
    {
        if ($fullName === '') {
            throw new DomainException('O nome é obrigatório.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Introduza um email válido.');
        }
        if ($passwordRequired && !$this->isStrongPassword($password)) {
            throw new DomainException('A palavra-passe deve ter pelo menos 8 caracteres, uma maiúscula, uma minúscula e um número.');
        }
        if (!$passwordRequired && $password !== '' && !$this->isStrongPassword($password)) {
            throw new DomainException('A palavra-passe deve ter pelo menos 8 caracteres, uma maiúscula, uma minúscula e um número.');
        }

        $emailQuery = 'SELECT id FROM users WHERE email = :email';
        $parameters = ['email' => $email];
        if ($exceptUserId !== null) {
            $emailQuery .= ' AND id <> :id';
            $parameters['id'] = $exceptUserId;
        }
        $statement = $connection->prepare($emailQuery . ' LIMIT 1');
        $statement->execute($parameters);
        if ($statement->fetchColumn()) {
            throw new DomainException('Já existe um utilizador com este email.');
        }

        $role = $connection->prepare('SELECT id FROM roles WHERE id = :id AND is_active = 1 LIMIT 1');
        $role->execute(['id' => $roleId]);
        if (!$role->fetchColumn()) {
            throw new DomainException('O papel selecionado é inválido.');
        }
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }
}