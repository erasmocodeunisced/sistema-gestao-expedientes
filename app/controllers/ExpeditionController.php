<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuditLogger.php';

final class ExpeditionController
{
    private const STATUSES = [
        'received' => 'Recebido',
        'in_progress' => 'Em Tramitação',
        'awaiting_dispatch' => 'Aguardando Despacho',
        'archived' => 'Arquivado',
        'completed' => 'Concluído',
    ];

    public function list(PDO $connection, string $search = '', string $status = ''): array
    {
                $conditions = [
                        '(e.reference_code LIKE :reference_code
                            OR e.subject LIKE :subject
                            OR e.origin LIKE :origin
                            OR e.destination LIKE :destination
                            OR e.status LIKE :status_search
                            OR CASE e.status
                                        WHEN "received" THEN "Recebido"
                                        WHEN "in_progress" THEN "Em Tramitação"
                                        WHEN "awaiting_dispatch" THEN "Aguardando Despacho"
                                        WHEN "archived" THEN "Arquivado"
                                        WHEN "completed" THEN "Concluído"
                                    END LIKE :status_label_search)',
                ];
        $parameters = [
            'reference_code' => '%' . $search . '%',
            'subject' => '%' . $search . '%',
            'origin' => '%' . $search . '%',
            'destination' => '%' . $search . '%',
            'status_search' => '%' . $search . '%',
            'status_label_search' => '%' . $search . '%',
        ];

        if ($status !== '' && isset(self::STATUSES[$status])) {
            $conditions[] = 'e.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $connection->prepare(
            'SELECT e.id, e.reference_code, e.subject, e.origin, e.destination,
                    e.status, e.received_at, e.created_at, u.full_name AS created_by_name
             FROM expedientes e
             LEFT JOIN users u ON u.id = e.created_by
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY e.received_at DESC, e.id DESC'
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function find(PDO $connection, int $id): ?array
    {
        $statement = $connection->prepare(
            'SELECT e.*, creator.full_name AS created_by_name, updater.full_name AS updated_by_name
             FROM expedientes e
             LEFT JOIN users creator ON creator.id = e.created_by
             LEFT JOIN users updater ON updater.id = e.updated_by
             WHERE e.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $expedition = $statement->fetch();

        return $expedition ?: null;
    }

    public function getStatuses(): array
    {
        return self::STATUSES;
    }

    public function create(PDO $connection, array $data, int $userId): int
    {
        $this->validate($connection, $data);
        $statement = $connection->prepare(
            'INSERT INTO expedientes
                (reference_code, subject, description, document_type, origin, destination,
                 priority, status, received_at, created_by, updated_by)
             VALUES
                (:reference_code, :subject, :description, :document_type, :origin, :destination,
                 :priority, :status, :received_at, :created_by, :updated_by)'
        );
        $statement->execute([
            'reference_code' => $data['reference_code'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?: null,
            'document_type' => $data['document_type'] ?: null,
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'received_at' => $data['received_at'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $expeditionId = (int) $connection->lastInsertId();
        AuditLogger::record($connection, 'expedition_created', 'expediente', $expeditionId, null, ['reference_code' => $data['reference_code'], 'subject' => $data['subject'], 'status' => $data['status']], $userId);

        return $expeditionId;
    }

    public function update(PDO $connection, int $id, array $data, int $userId): void
    {
        if (!$this->find($connection, $id)) {
            throw new DomainException('Expediente não encontrado.');
        }
        $this->validate($connection, $data, $id);
        $before = $this->find($connection, $id);
        if (!$before) {
            throw new DomainException('Expediente não encontrado.');
        }
        $statement = $connection->prepare(
            'UPDATE expedientes SET
                reference_code = :reference_code, subject = :subject, description = :description,
                document_type = :document_type, origin = :origin, destination = :destination,
                priority = :priority, status = :status, received_at = :received_at, updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute([
            'reference_code' => $data['reference_code'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?: null,
            'document_type' => $data['document_type'] ?: null,
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'received_at' => $data['received_at'],
            'updated_by' => $userId,
            'id' => $id,
        ]);
        AuditLogger::record($connection, 'expedition_updated', 'expediente', $id, ['reference_code' => $before['reference_code'], 'subject' => $before['subject'], 'status' => $before['status']], ['reference_code' => $data['reference_code'], 'subject' => $data['subject'], 'status' => $data['status']], $userId);
    }

    public function changeStatus(PDO $connection, int $id, string $status, int $userId): void
    {
        if (!isset(self::STATUSES[$status])) {
            throw new DomainException('O estado selecionado é inválido.');
        }
        $before = $this->find($connection, $id);
        if (!$before) {
            throw new DomainException('Expediente não encontrado.');
        }
        $statement = $connection->prepare(
            'UPDATE expedientes SET status = :status, closed_at = :closed_at, updated_by = :updated_by WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'closed_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null,
            'updated_by' => $userId,
            'id' => $id,
        ]);
        AuditLogger::record($connection, 'expedition_status_changed', 'expediente', $id, ['status' => $before['status']], ['status' => $status], $userId);
    }

    private function validate(PDO $connection, array $data, ?int $exceptId = null): void
    {
        foreach (['reference_code', 'subject', 'origin', 'destination', 'received_at'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new DomainException('O campo ' . $this->fieldLabel($field) . ' é obrigatório.');
            }
        }
        if (!isset(self::STATUSES[$data['status'] ?? ''])) {
            throw new DomainException('O estado selecionado é inválido.');
        }
        if (!in_array($data['priority'] ?? '', ['low', 'normal', 'high', 'urgent'], true)) {
            throw new DomainException('A prioridade selecionada é inválida.');
        }
        $date = DateTime::createFromFormat('Y-m-d\TH:i', (string) $data['received_at']);
        if (!$date || $date->format('Y-m-d\TH:i') !== $data['received_at']) {
            throw new DomainException('A data de entrada é inválida.');
        }

        $query = 'SELECT id FROM expedientes WHERE reference_code = :reference_code';
        $parameters = ['reference_code' => $data['reference_code']];
        if ($exceptId !== null) {
            $query .= ' AND id <> :id';
            $parameters['id'] = $exceptId;
        }
        $statement = $connection->prepare($query . ' LIMIT 1');
        $statement->execute($parameters);
        if ($statement->fetchColumn()) {
            throw new DomainException('Já existe um expediente com este número/código.');
        }
    }

    private function fieldLabel(string $field): string
    {
        return [
            'reference_code' => 'número/código',
            'subject' => 'assunto',
            'origin' => 'remetente',
            'destination' => 'destinatário/setor',
            'received_at' => 'data de entrada',
        ][$field] ?? $field;
    }
}