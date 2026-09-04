<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuditLogger.php';

final class DespachoController
{
    private const STATUSES = [
        'registered' => 'Registado',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
        'forwarded' => 'Encaminhado',
    ];

    public function list(PDO $connection, string $search = '', ?int $expeditionId = null): array
    {
        $term = '%' . $search . '%';
        $expeditionCondition = '';
        $parameters = [
            'reference_code' => $term,
            'subject' => $term,
            'content' => $term,
            'decision' => $term,
            'status' => $term,
        ];
        if ($expeditionId !== null) {
            $expeditionCondition = ' AND e.id = :expedition_id';
            $parameters['expedition_id'] = $expeditionId;
        }
        $statement = $connection->prepare(
            'SELECT d.id, d.expediente_id, d.content, d.decision, d.status, d.created_at,
                    e.reference_code, e.subject, u.full_name AS author_name
             FROM despachos d
             INNER JOIN expedientes e ON e.id = d.expediente_id
             LEFT JOIN users u ON u.id = d.author_user_id
                 WHERE (e.reference_code LIKE :reference_code
                OR e.subject LIKE :subject
                OR d.content LIKE :content
                OR d.decision LIKE :decision
                     OR d.status LIKE :status)' . $expeditionCondition . '
             ORDER BY d.created_at DESC, d.id DESC'
        );
          $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function find(PDO $connection, int $id): ?array
    {
        $statement = $connection->prepare(
            'SELECT d.*, e.reference_code, e.subject, u.full_name AS author_name
             FROM despachos d
             INNER JOIN expedientes e ON e.id = d.expediente_id
             LEFT JOIN users u ON u.id = d.author_user_id
             WHERE d.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $dispatch = $statement->fetch();

        return $dispatch ?: null;
    }

    public function history(PDO $connection, int $expeditionId): array
    {
        $statement = $connection->prepare(
            'SELECT d.id, d.content, d.decision, d.observation, d.status, d.created_at,
                    u.full_name AS author_name
             FROM despachos d
             LEFT JOIN users u ON u.id = d.author_user_id
             WHERE d.expediente_id = :expediente_id
             ORDER BY d.created_at ASC, d.id ASC'
        );
        $statement->execute(['expediente_id' => $expeditionId]);

        return $statement->fetchAll();
    }

    public function statuses(): array
    {
        return self::STATUSES;
    }

    public function create(PDO $connection, int $expeditionId, int $authorUserId, string $content, string $decision, string $observation, string $createdAt, string $status): int
    {
        $this->validate($connection, $expeditionId, $content, $createdAt, $status);
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'INSERT INTO despachos
                    (expediente_id, author_user_id, content, decision, observation, status, created_at)
                 VALUES
                    (:expediente_id, :author_user_id, :content, :decision, :observation, :status, :created_at)'
            );
            $statement->execute([
                'expediente_id' => $expeditionId,
                'author_user_id' => $authorUserId,
                'content' => $content,
                'decision' => $decision !== '' ? $decision : null,
                'observation' => $observation !== '' ? $observation : null,
                'status' => $status,
                'created_at' => str_replace('T', ' ', $createdAt) . ':00',
            ]);
            $dispatchId = (int) $connection->lastInsertId();
            AuditLogger::record($connection, 'despacho_created', 'despacho', $dispatchId, null, ['expediente_id' => $expeditionId, 'decision' => $decision, 'status' => $status], $authorUserId);
            $expeditionStatus = in_array($status, ['approved', 'rejected'], true)
                ? 'completed'
                : 'awaiting_dispatch';
            $update = $connection->prepare(
                'UPDATE expedientes SET status = :status, closed_at = :closed_at, updated_by = :updated_by WHERE id = :id'
            );
            $update->execute([
                'status' => $expeditionStatus,
                'closed_at' => $expeditionStatus === 'completed' ? date('Y-m-d H:i:s') : null,
                'updated_by' => $authorUserId,
                'id' => $expeditionId,
            ]);
            $connection->commit();

            return $dispatchId;
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    private function validate(PDO $connection, int $expeditionId, string $content, string $createdAt, string $status): void
    {
        $expedition = $connection->prepare('SELECT id FROM expedientes WHERE id = :id LIMIT 1');
        $expedition->execute(['id' => $expeditionId]);
        if (!$expedition->fetchColumn()) {
            throw new DomainException('Expediente não encontrado.');
        }
        if (trim($content) === '') {
            throw new DomainException('O conteúdo do despacho é obrigatório.');
        }
        if (strlen($content) > 10000) {
            throw new DomainException('O conteúdo do despacho excede o limite permitido.');
        }
        if (!isset(self::STATUSES[$status])) {
            throw new DomainException('O estado do despacho é inválido.');
        }
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $createdAt);
        if (!$date || $date->format('Y-m-d\TH:i') !== $createdAt) {
            throw new DomainException('A data do despacho é inválida.');
        }
    }
}