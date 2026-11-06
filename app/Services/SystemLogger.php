<?php

class SystemLogger
{
    public static function log(
        PDO $pdo,
        string $eventType,
        string $severity,
        ?int $userId,
        ?string $username,
        string $message
    ): void {
        // Record the address that connected to PHP directly. Forwarded headers are
        // intentionally ignored because they can be supplied by an untrusted client.
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipAddress = filter_var($remoteIp, FILTER_VALIDATE_IP) !== false ? $remoteIp : null;

        $stmt = $pdo->prepare(
            'INSERT INTO system_logs (event_type, severity, user_id, username, ip_address, message)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            substr($eventType, 0, 40),
            $severity,
            $userId,
            $username !== null ? substr($username, 0, 100) : null,
            $ipAddress,
            substr($message, 0, 255),
        ]);
    }
}
