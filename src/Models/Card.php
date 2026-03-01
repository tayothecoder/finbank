<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// card model

class Card
{
    public static function findById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM cards WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByUser(string $internetId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM cards WHERE internet_id = :iid ORDER BY created_at DESC');
        $stmt->execute(['iid' => $internetId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(array $data): ?int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO cards (internet_id, card_number, card_name, card_type, expiry_date, cvv_hash, status, fee)
             VALUES (:iid, :num, :name, :type, :exp, :cvv, :status, :fee)'
        );
        $ok = $stmt->execute([
            'iid'    => $data['internet_id'],
            'num'    => $data['card_number'],
            'name'   => $data['card_name'],
            'type'   => $data['card_type'],
            'exp'    => $data['expiry_date'],
            'cvv'    => $data['cvv_hash'],
            'status' => $data['status'] ?? 'pending',
            'fee'    => $data['fee'] ?? 0,
        ]);
        return $ok ? (int) $db->lastInsertId() : null;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $valid = ['pending', 'active', 'frozen', 'cancelled'];
        if (!in_array($status, $valid, true)) return false;

        $db = Database::connect();
        $stmt = $db->prepare('UPDATE cards SET status = :s WHERE id = :id');
        return $stmt->execute(['s' => $status, 'id' => $id]);
    }

    public static function countByUser(string $internetId): int
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT COUNT(*) FROM cards WHERE internet_id = :iid');
        $stmt->execute(['iid' => $internetId]);
        return (int) $stmt->fetchColumn();
    }
}
