<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// audit log

class AuditLog
{
    public static function log(string $internetId, string $action, string $details = ''): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO audit_logs (internet_id, action, details, ip_address, user_agent)
             VALUES (:iid, :action, :details, :ip, :ua)'
        );
        return $stmt->execute([
            'iid'     => $internetId,
            'action'  => $action,
            'details' => $details,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'ua'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    }

    public static function getByUser(string $internetId, int $limit = 50): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT * FROM audit_logs WHERE internet_id = :iid ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue('iid', $internetId);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getRecent(int $limit = 100): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :lim');
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
