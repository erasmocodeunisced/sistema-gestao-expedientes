<?php

declare(strict_types=1);

final class RelatorioController
{
    private const STATUSES = [
        'received' => 'Recebido',
        'in_progress' => 'Em Tramitação',
        'awaiting_dispatch' => 'Aguardando Despacho',
        'archived' => 'Arquivado',
        'completed' => 'Concluído',
    ];

    public function normalizeFilters(?string $from, ?string $to, mixed $status, mixed $userId, ?string $destination): array
    {
        $from = $this->validDate($from);
        $to = $this->validDate($to);
        if ($from !== '' && $to !== '' && $from > $to) {
            throw new DomainException('A data inicial não pode ser posterior à data final.');
        }

        $userId = filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return [
            'from' => $from,
            'to' => $to,
            'status' => is_string($status) && isset(self::STATUSES[$status]) ? $status : '',
            'user_id' => $userId === false ? null : $userId,
            'destination' => is_string($destination) ? trim($destination) : '',
        ];
    }

    public function statuses(): array
    {
        return self::STATUSES;
    }

    public function users(PDO $connection): array
    {
        return $connection->query('SELECT id, full_name, email FROM users ORDER BY full_name')->fetchAll();
    }

    public function summary(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->expeditionFilter($filters, 'e.received_at', 'summary');
        $statement = $connection->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(e.status = "received") AS received,
                    SUM(e.status = "in_progress") AS in_progress,
                    SUM(e.status = "awaiting_dispatch") AS awaiting_dispatch,
                    SUM(e.status = "completed") AS completed,
                    SUM(e.status = "archived") AS archived
             FROM expedientes e ' . $where
        );
        $statement->execute($parameters);
        $summary = $statement->fetch() ?: [];
        $summary['tramitation_count'] = $this->countTramitacoes($connection, $filters);
        $summary['dispatch_count'] = $this->countDespachos($connection, $filters);
        $summary['audit_count'] = $this->countAuditoria($connection, $filters);

        return array_map(static fn ($value): int => (int) ($value ?? 0), $summary);
    }

    public function byStatus(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->expeditionFilter($filters, 'e.received_at', 'status');
        $statement = $connection->prepare(
            'SELECT e.status, COUNT(*) AS total
             FROM expedientes e ' . $where . '
             GROUP BY e.status ORDER BY total DESC'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function byPeriod(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->expeditionFilter($filters, 'e.received_at', 'period');
        $statement = $connection->prepare(
            'SELECT DATE(e.received_at) AS period_date, COUNT(*) AS total
             FROM expedientes e ' . $where . '
             GROUP BY DATE(e.received_at) ORDER BY period_date DESC'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function archived(PDO $connection, array $filters): array
    {
        $archiveFilters = $filters;
        $archiveFilters['user_id'] = null;
        [$where, $parameters] = $this->expeditionFilter($archiveFilters, 'a.archived_at', 'archive');
        if ($filters['user_id'] !== null) {
            $where .= ($where ? ' AND ' : ' WHERE ') . 'a.archived_by = :archive_user';
            $parameters['archive_user'] = $filters['user_id'];
        }
        $statement = $connection->prepare(
            'SELECT e.reference_code, e.subject, e.destination, a.archive_reference,
                    a.archived_at, u.full_name AS archived_by_name
             FROM arquivos a
             INNER JOIN expedientes e ON e.id = a.expediente_id
             LEFT JOIN users u ON u.id = a.archived_by ' . $where . '
             ORDER BY a.archived_at DESC'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function tramitacoes(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->activityFilter($filters, 't.sent_at', 'tram');
        $statement = $connection->prepare(
            'SELECT t.destination, COUNT(*) AS total
             FROM tramitacoes t INNER JOIN expedientes e ON e.id = t.expediente_id ' . $where . '
             GROUP BY t.destination ORDER BY total DESC, t.destination'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function despachos(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->activityFilter($filters, 'd.created_at', 'dispatch');
        $statement = $connection->prepare(
            'SELECT d.status, COUNT(*) AS total
             FROM despachos d INNER JOIN expedientes e ON e.id = d.expediente_id ' . $where . '
             GROUP BY d.status ORDER BY total DESC'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function userActivity(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->auditFilter($filters, 'activity');
        $statement = $connection->prepare(
            'SELECT COALESCE(u.full_name, "Sistema/Anónimo") AS user_name,
                    COUNT(*) AS total
             FROM auditoria a LEFT JOIN users u ON u.id = a.user_id ' . $where . '
             GROUP BY a.user_id, u.full_name ORDER BY total DESC'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function auditEvents(PDO $connection, array $filters): array
    {
        [$where, $parameters] = $this->auditFilter($filters, 'events');
        $statement = $connection->prepare(
            'SELECT a.action, a.entity_type, COUNT(*) AS total, MAX(a.created_at) AS last_at
             FROM auditoria a ' . $where . '
             GROUP BY a.action, a.entity_type ORDER BY total DESC, last_at DESC'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    private function countTramitacoes(PDO $connection, array $filters): int
    {
        [$where, $parameters] = $this->activityFilter($filters, 't.sent_at', 'count_tram');
        $statement = $connection->prepare('SELECT COUNT(*) FROM tramitacoes t INNER JOIN expedientes e ON e.id=t.expediente_id ' . $where);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    private function countDespachos(PDO $connection, array $filters): int
    {
        [$where, $parameters] = $this->activityFilter($filters, 'd.created_at', 'count_dispatch');
        $statement = $connection->prepare('SELECT COUNT(*) FROM despachos d INNER JOIN expedientes e ON e.id=d.expediente_id ' . $where);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    private function countAuditoria(PDO $connection, array $filters): int
    {
        [$where, $parameters] = $this->auditFilter($filters, 'count_audit');
        $statement = $connection->prepare('SELECT COUNT(*) FROM auditoria a ' . $where);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    private function expeditionFilter(array $filters, string $dateColumn, string $prefix): array
    {
        $conditions = [];
        $parameters = [];
        if ($filters['from'] !== '') { $conditions[] = $dateColumn . ' >= :' . $prefix . '_from'; $parameters[$prefix . '_from'] = $filters['from'] . ' 00:00:00'; }
        if ($filters['to'] !== '') { $conditions[] = $dateColumn . ' <= :' . $prefix . '_to'; $parameters[$prefix . '_to'] = $filters['to'] . ' 23:59:59'; }
        if ($filters['status'] !== '') { $conditions[] = 'e.status = :' . $prefix . '_status'; $parameters[$prefix . '_status'] = $filters['status']; }
        if ($filters['user_id'] !== null) { $conditions[] = 'e.created_by = :' . $prefix . '_user'; $parameters[$prefix . '_user'] = $filters['user_id']; }
        if ($filters['destination'] !== '') { $conditions[] = 'e.destination LIKE :' . $prefix . '_destination'; $parameters[$prefix . '_destination'] = '%' . $filters['destination'] . '%'; }
        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $parameters];
    }

    private function activityFilter(array $filters, string $dateColumn, string $prefix): array
    {
        $conditions = [];
        $parameters = [];
        $isDispatch = str_contains($prefix, 'dispatch');
        if ($filters['from'] !== '') { $conditions[] = $dateColumn . ' >= :' . $prefix . '_from'; $parameters[$prefix . '_from'] = $filters['from'] . ' 00:00:00'; }
        if ($filters['to'] !== '') { $conditions[] = $dateColumn . ' <= :' . $prefix . '_to'; $parameters[$prefix . '_to'] = $filters['to'] . ' 23:59:59'; }
        if ($filters['status'] !== '') { $conditions[] = 'e.status = :' . $prefix . '_status'; $parameters[$prefix . '_status'] = $filters['status']; }
        if ($filters['user_id'] !== null) {
            $conditions[] = $isDispatch
                ? 'd.author_user_id = :' . $prefix . '_user'
                : '(t.sender_user_id = :' . $prefix . '_user OR t.responsible_user_id = :' . $prefix . '_user)';
            $parameters[$prefix . '_user'] = $filters['user_id'];
        }
        if ($filters['destination'] !== '') {
            $conditions[] = ($isDispatch ? 'e.destination' : 't.destination') . ' LIKE :' . $prefix . '_destination';
            $parameters[$prefix . '_destination'] = '%' . $filters['destination'] . '%';
        }
        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $parameters];
    }

    private function auditFilter(array $filters, string $prefix): array
    {
        $conditions = [];
        $parameters = [];
        if ($filters['from'] !== '') { $conditions[] = 'a.created_at >= :' . $prefix . '_from'; $parameters[$prefix . '_from'] = $filters['from'] . ' 00:00:00'; }
        if ($filters['to'] !== '') { $conditions[] = 'a.created_at <= :' . $prefix . '_to'; $parameters[$prefix . '_to'] = $filters['to'] . ' 23:59:59'; }
        if ($filters['user_id'] !== null) { $conditions[] = 'a.user_id = :' . $prefix . '_user'; $parameters[$prefix . '_user'] = $filters['user_id']; }
        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $parameters];
    }

    private function validDate(?string $date): string
    {
        if ($date === null || $date === '') { return ''; }
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) { throw new DomainException('O período informado é inválido.'); }
        return $date;
    }
}