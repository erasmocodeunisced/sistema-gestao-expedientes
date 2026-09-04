<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuditLogger.php';

final class TramitacaoController
{
    private const STATUSES = [
        'sent' => 'Encaminhada',
        'received' => 'Recebida',
        'completed' => 'Concluída',
    ];

    public function history(PDO $connection, int $expeditionId): array
    {
        $statement = $connection->prepare(
            'SELECT t.id, t.destination, t.observation, t.status, t.sent_at,
                    sender.full_name AS sender_name,
                    responsible.full_name AS responsible_name
             FROM tramitacoes t
             LEFT JOIN users sender ON sender.id = t.sender_user_id
             LEFT JOIN users responsible ON responsible.id = t.responsible_user_id
             WHERE t.expediente_id = :expediente_id
             ORDER BY t.sent_at ASC, t.id ASC'
        );
        $statement->execute(['expediente_id' => $expeditionId]);

        return $statement->fetchAll();
    }

    public function getActiveUsers(PDO $connection): array
    {
        return $connection->query(
            'SELECT id, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name'
        )->fetchAll();
    }

    public function getStatuses(): array
    {
        return self::STATUSES;
    }

    public function create(PDO $connection, int $expeditionId, int $senderUserId, string $destination, int $responsibleUserId, string $sentAt, string $observation, string $status = 'sent'): int
    {
        $this->validate($connection, $expeditionId, $responsibleUserId, $destination, $sentAt, $status);
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'INSERT INTO tramitacoes
                    (expediente_id, sender_user_id, recipient_user_id, destination,
                     responsible_user_id, observation, status, sent_at)
                 VALUES
                    (:expediente_id, :sender_user_id, :recipient_user_id, :destination,
                     :responsible_user_id, :observation, :status, :sent_at)'
            );
            $statement->execute([
                'expediente_id' => $expeditionId,
                'sender_user_id' => $senderUserId,
                'recipient_user_id' => $responsibleUserId,
                'destination' => $destination,
                'responsible_user_id' => $responsibleUserId,
                'observation' => $observation !== '' ? $observation : null,
                'status' => $status,
                'sent_at' => str_replace('T', ' ', $sentAt) . ':00',
            ]);
            $tramitacaoId = (int) $connection->lastInsertId();
            AuditLogger::record($connection, 'tramitacao_created', 'tramitacao', $tramitacaoId, null, ['expediente_id' => $expeditionId, 'destination' => $destination, 'responsible_user_id' => $responsibleUserId, 'status' => $status], $senderUserId);

            $update = $connection->prepare(
                'UPDATE expedientes SET status = :status, closed_at = NULL, updated_by = :updated_by WHERE id = :id'
            );
            $update->execute([
                'status' => 'in_progress',
                'updated_by' => $senderUserId,
                'id' => $expeditionId,
            ]);
            $connection->commit();

            return $tramitacaoId;
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    private function validate(PDO $connection, int $expeditionId, int $responsibleUserId, string $destination, string $sentAt, string $status): void
    {
        $expedition = $connection->prepare('SELECT id FROM expedientes WHERE id = :id LIMIT 1');
        $expedition->execute(['id' => $expeditionId]);
        if (!$expedition->fetchColumn()) {
            throw new DomainException('Expediente não encontrado.');
        }
        if ($destination === '' || strlen($destination) > 150) {
            throw new DomainException('Indique um destino válido.');
        }
        $responsible = $connection->prepare(
            'SELECT id FROM users WHERE id = :id AND is_active = 1 LIMIT 1'
        );
        $responsible->execute(['id' => $responsibleUserId]);
        if (!$responsible->fetchColumn()) {
            throw new DomainException('O responsável selecionado é inválido.');
        }
        if (!isset(self::STATUSES[$status])) {
            throw new DomainException('O estado da tramitação é inválido.');
        }
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $sentAt);
        if (!$date || $date->format('Y-m-d\TH:i') !== $sentAt) {
            throw new DomainException('A data da tramitação é inválida.');
        }
    }
}