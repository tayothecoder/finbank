<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// transaction model

class Transaction
{
    public static function findById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByUser(string $internetId, int $limit = 20, int $offset = 0, array $filters = []): array
    {
        $db = Database::connect();
        $where = 'WHERE internet_id = :iid';
        $params = ['iid' => $internetId];

        if (!empty($filters['type'])) {
            $where .= ' AND type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND created_at >= :dfrom';
            $params['dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND created_at <= :dto';
            $params['dto'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM transactions {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function getRecent(string $internetId, int $limit = 10): array
    {
        return self::getByUser($internetId, $limit, 0);
    }

    public static function countByUser(string $internetId, array $filters = []): int
    {
        $db = Database::connect();
        $where = 'WHERE internet_id = :iid';
        $params = ['iid' => $internetId];

        if (!empty($filters['type'])) {
            $where .= ' AND type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND created_at >= :dfrom';
            $params['dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND created_at <= :dto';
            $params['dto'] = $filters['date_to'] . ' 23:59:59';
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data): ?int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO transactions (internet_id, type, amount, fee, currency, status, reference_id, description,
             payment_account, recipient_name, recipient_account, recipient_bank, recipient_country,
             swift_code, routing_number, bank_address, trans_type)
             VALUES (:iid, :type, :amt, :fee, :cur, :status, :ref, :desc, :pa, :rn, :ra, :rb, :rc, :sc, :rout, :ba, :tt)'
        );
        $ok = $stmt->execute([
            'iid'    => $data['internet_id'],
            'type'   => $data['type'],
            'amt'    => $data['amount'],
            'fee'    => $data['fee'] ?? 0,
            'cur'    => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'pending',
            'ref'    => $data['reference_id'],
            'desc'   => $data['description'] ?? null,
            'pa'     => $data['payment_account'] ?? null,
            'rn'     => $data['recipient_name'] ?? null,
            'ra'     => $data['recipient_account'] ?? null,
            'rb'     => $data['recipient_bank'] ?? null,
            'rc'     => $data['recipient_country'] ?? null,
            'sc'     => $data['swift_code'] ?? null,
            'rout'   => $data['routing_number'] ?? null,
            'ba'     => $data['bank_address'] ?? null,
            'tt'     => $data['trans_type'],
        ]);
        return $ok ? (int) $db->lastInsertId() : null;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $valid = ['pending', 'processing', 'completed', 'failed'];
        if (!in_array($status, $valid, true)) return false;

        $db = Database::connect();
        $stmt = $db->prepare('UPDATE transactions SET status = :s WHERE id = :id');
        return $stmt->execute(['s' => $status, 'id' => $id]);
    }

    // monthly totals comparison
    public static function getMonthlyStats(string $internetId): array
    {
        $db = Database::connect();
        $sql = "SELECT
            SUM(CASE WHEN trans_type = 'credit' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN amount ELSE 0 END) as credits_this_month,
            SUM(CASE WHEN trans_type = 'debit' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN amount ELSE 0 END) as debits_this_month,
            SUM(CASE WHEN trans_type = 'credit' AND created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
                AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01') THEN amount ELSE 0 END) as credits_last_month,
            SUM(CASE WHEN trans_type = 'debit' AND created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
                AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01') THEN amount ELSE 0 END) as debits_last_month
            FROM transactions WHERE internet_id = :iid AND status = 'completed'";
        $stmt = $db->prepare($sql);
        $stmt->execute(['iid' => $internetId]);
        $row = $stmt->fetch();
        return [
            'credits_this' => (float) ($row['credits_this_month'] ?? 0),
            'debits_this'  => (float) ($row['debits_this_month'] ?? 0),
            'credits_last' => (float) ($row['credits_last_month'] ?? 0),
            'debits_last'  => (float) ($row['debits_last_month'] ?? 0),
        ];
    }
}
