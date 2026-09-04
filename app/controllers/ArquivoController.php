<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuditLogger.php';

final class ArquivoController
{
    public function list(PDO $connection, string $search = ''): array
    {
        $term = '%' . $search . '%';
        $statement = $connection->prepare(
            'SELECT a.id, a.expediente_id, a.archive_reference, a.notes, a.archived_at,
                    e.reference_code, e.subject, e.origin, e.destination,
                    u.full_name AS archived_by_name
             FROM arquivos a
             INNER JOIN expedientes e ON e.id = a.expediente_id
             LEFT JOIN users u ON u.id = a.archived_by
             WHERE e.reference_code LIKE :reference_code
                OR e.subject LIKE :subject
                OR e.origin LIKE :origin
                OR e.destination LIKE :destination
                OR a.archive_reference LIKE :archive_reference
                OR a.notes LIKE :notes
             ORDER BY a.archived_at DESC, a.id DESC'
        );
        $statement->execute([
            'reference_code' => $term,
            'subject' => $term,
            'origin' => $term,
            'destination' => $term,
            'archive_reference' => $term,
            'notes' => $term,
        ]);

        return $statement->fetchAll();
    }

    public function findByExpedition(PDO $connection, int $expeditionId): ?array
    {
        $statement = $connection->prepare(
            'SELECT a.*, u.full_name AS archived_by_name
             FROM arquivos a
             LEFT JOIN users u ON u.id = a.archived_by
             WHERE a.expediente_id = :expediente_id LIMIT 1'
        );
        $statement->execute(['expediente_id' => $expeditionId]);
        $archive = $statement->fetch();

        return $archive ?: null;
    }

    public function findById(PDO $connection, int $archiveId): ?array
    {
        $statement = $connection->prepare(
            'SELECT a.*, e.reference_code, e.subject, e.description, e.document_type,
                    e.origin, e.destination, e.priority, e.status, e.received_at,
                    u.full_name AS archived_by_name
             FROM arquivos a
             INNER JOIN expedientes e ON e.id = a.expediente_id
             LEFT JOIN users u ON u.id = a.archived_by
             WHERE a.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $archiveId]);
        $archive = $statement->fetch();

        return $archive ?: null;
    }

    public function archive(PDO $connection, int $expeditionId, int $userId, string $archiveReference, string $notes): int
    {
        $expedition = $connection->prepare(
            'SELECT id, status FROM expedientes WHERE id = :id LIMIT 1'
        );
        $expedition->execute(['id' => $expeditionId]);
        $record = $expedition->fetch();
        if (!$record) {
            throw new DomainException('Expediente não encontrado.');
        }
        if ($this->findByExpedition($connection, $expeditionId)) {
            throw new DomainException('Este expediente já está arquivado.');
        }
        if ($record['status'] !== 'completed') {
            throw new DomainException('Só é possível arquivar expedientes concluídos.');
        }
        if ($archiveReference !== '' && strlen($archiveReference) > 150) {
            throw new DomainException('A referência de arquivo excede o limite permitido.');
        }
        if (strlen($notes) > 5000) {
            throw new DomainException('A observação excede o limite permitido.');
        }

        $connection->beginTransaction();
        try {
            $statement = $connection->prepare(
                'INSERT INTO arquivos
                    (expediente_id, archived_by, archive_reference, notes, archived_at)
                 VALUES (:expediente_id, :archived_by, :archive_reference, :notes, NOW())'
            );
            $statement->execute([
                'expediente_id' => $expeditionId,
                'archived_by' => $userId,
                'archive_reference' => $archiveReference !== '' ? $archiveReference : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);
            $archiveId = (int) $connection->lastInsertId();
            AuditLogger::record($connection, 'expediente_archived', 'arquivo', $archiveId, null, ['expediente_id' => $expeditionId, 'archive_reference' => $archiveReference], $userId);

            $update = $connection->prepare(
                'UPDATE expedientes SET status = :status, updated_by = :updated_by WHERE id = :id'
            );
            $update->execute([
                'status' => 'archived',
                'updated_by' => $userId,
                'id' => $expeditionId,
            ]);
            $connection->commit();

            return $archiveId;
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
}