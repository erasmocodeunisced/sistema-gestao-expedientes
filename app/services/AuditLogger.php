<?php

declare(strict_types=1);

final class AuditLogger
{
    public static function record(
        PDO $connection,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): void {
        try {
            $statement = $connection->prepare(
                'INSERT INTO auditoria
                    (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES
                    (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)'
            );
            $statement->execute([
                'user_id' => $userId ?? self::sessionUserId(),
                'action' => substr($action, 0, 100),
                'entity_type' => substr($entityType, 0, 100),
                'entity_id' => $entityId,
                'old_values' => self::encode($oldValues),
                'new_values' => self::encode($newValues),
                'ip_address' => self::clientIp(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            ]);
        } catch (Throwable $exception) {
            error_log('Audit logging failed: ' . $exception->getMessage());
        }
    }

    private static function sessionUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    private static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private static function encode(?array $values): ?string
    {
        if ($values === null) {
            return null;
        }

        $json = json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? null : $json;
    }
}