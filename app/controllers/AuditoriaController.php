<?php

declare(strict_types=1);

final class AuditoriaController
{
    public function list(PDO $connection, string $search, ?int $userId, string $action, string $entityType, string $from, string $to, int $page, int $perPage = 20): array
    {
        $conditions = [];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(a.action LIKE :search_action OR a.entity_type LIKE :search_entity OR CAST(a.entity_id AS CHAR) LIKE :search_id)';
            $parameters['search_action'] = '%' . $search . '%';
            $parameters['search_entity'] = '%' . $search . '%';
            $parameters['search_id'] = '%' . $search . '%';
        }
        if ($userId !== null) {
            $conditions[] = 'a.user_id = :user_id';
            $parameters['user_id'] = $userId;
        }
        if ($action !== '') {
            $conditions[] = 'a.action = :action';
            $parameters['action'] = $action;
        }
        if ($entityType !== '') {
            $conditions[] = 'a.entity_type = :entity_type';
            $parameters['entity_type'] = $entityType;
        }
        if ($from !== '') {
            $conditions[] = 'a.created_at >= :date_from';
            $parameters['date_from'] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $conditions[] = 'a.created_at <= :date_to';
            $parameters['date_to'] = $to . ' 23:59:59';
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;
        $count = $connection->prepare('SELECT COUNT(*) FROM auditoria a ' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $statement = $connection->prepare(
            'SELECT a.id, a.user_id, a.action, a.entity_type, a.entity_id,
                    a.old_values, a.new_values, a.ip_address, a.created_at,
                    u.full_name AS user_name, u.email AS user_email
             FROM auditoria a
             LEFT JOIN users u ON u.id = a.user_id
             ' . $where . '
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return ['items' => $statement->fetchAll(), 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function users(PDO $connection): array
    {
        return $connection->query('SELECT id, full_name, email FROM users ORDER BY full_name')->fetchAll();
    }

    public function actions(PDO $connection): array
    {
        return $connection->query('SELECT DISTINCT action FROM auditoria ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function entities(PDO $connection): array
    {
        return $connection->query('SELECT DISTINCT entity_type FROM auditoria ORDER BY entity_type')->fetchAll(PDO::FETCH_COLUMN);
    }
}