<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// ticket model

class Ticket
{
    public static function findById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM tickets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByUser(string $internetId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT * FROM tickets WHERE internet_id = :iid ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute(['iid' => $internetId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(array $data): ?int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO tickets (internet_id, subject, message, type, attachment)
             VALUES (:iid, :subj, :msg, :type, :att)'
        );
        $ok = $stmt->execute([
            'iid'  => $data['internet_id'],
            'subj' => $data['subject'],
            'msg'  => $data['message'],
            'type' => $data['type'] ?? 'general',
            'att'  => $data['attachment'] ?? null,
        ]);
        return $ok ? (int) $db->lastInsertId() : null;
    }

    public static function update(int $id, array $data): bool
    {
        $allowed = ['status', 'admin_reply'];
        $sets = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }
        if (empty($sets)) return false;
        $db = Database::connect();
        $sql = 'UPDATE tickets SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return $db->prepare($sql)->execute($params);
    }

    public static function addReply(int $id, string $reply): bool
    {
        return self::update($id, ['admin_reply' => $reply]);
    }

    public static function countByUser(string $internetId): int
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT COUNT(*) FROM tickets WHERE internet_id = :iid');
        $stmt->execute(['iid' => $internetId]);
        return (int) $stmt->fetchColumn();
    }

    public static function getOpen(string $internetId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT * FROM tickets WHERE internet_id = :iid AND status IN ('open','processing') ORDER BY created_at DESC"
        );
        $stmt->execute(['iid' => $internetId]);
        return $stmt->fetchAll() ?: [];
    }
}
