<?php

declare(strict_types=1);

final class DashboardController
{
    public function summary(PDO $connection): array
    {
        $summary = $connection->query(
            'SELECT COUNT(*) AS total,
                    SUM(status = "received") AS received,
                    SUM(status = "in_progress") AS in_progress,
                    SUM(status = "awaiting_dispatch") AS awaiting_dispatch,
                    SUM(status = "completed") AS completed,
                    SUM(status = "archived") AS archived
             FROM expedientes'
        )->fetch() ?: [];
        $users = $connection->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
        return [
            'total' => (int) ($summary['total'] ?? 0),
            'received' => (int) ($summary['received'] ?? 0),
            'in_progress' => (int) ($summary['in_progress'] ?? 0),
            'awaiting_dispatch' => (int) ($summary['awaiting_dispatch'] ?? 0),
            'completed' => (int) ($summary['completed'] ?? 0),
            'archived' => (int) ($summary['archived'] ?? 0),
            'users' => (int) $users,
        ];
    }

    public function recentActivity(PDO $connection): array
    {
        $statement = $connection->prepare(
            'SELECT a.action, a.entity_type, a.entity_id, a.created_at,
                    u.full_name AS user_name
             FROM auditoria a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC LIMIT 8'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
}