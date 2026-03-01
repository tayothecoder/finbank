<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// account model

class Account
{
    public static function findById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByInternetId(string $internetId): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM accounts WHERE internet_id = :iid');
        $stmt->execute(['iid' => $internetId]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM accounts WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): ?int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO accounts (internet_id, email, password_hash, pin_hash, first_name, last_name, phone, checking_acct_no, savings_acct_no)
             VALUES (:iid, :email, :pw, :pin, :fn, :ln, :phone, :cacct, :sacct)'
        );
        $ok = $stmt->execute([
            'iid'   => $data['internet_id'],
            'email' => strtolower($data['email']),
            'pw'    => $data['password_hash'],
            'pin'   => $data['pin_hash'],
            'fn'    => $data['first_name'],
            'ln'    => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'cacct' => $data['checking_acct_no'] ?? null,
            'sacct' => $data['savings_acct_no'] ?? null,
        ]);
        return $ok ? (int) $db->lastInsertId() : null;
    }

    public static function update(int $id, array $data): bool
    {
        $allowed = ['first_name', 'last_name', 'phone', 'gender', 'dob', 'address',
                     'state', 'avatar', 'currency', 'status', 'kyc_status',
                     'reset_token', 'reset_token_expires', 'transfer_enabled',
                     'manager_name', 'manager_email'];
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
        $sql = 'UPDATE accounts SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return $db->prepare($sql)->execute($params);
    }

    public static function updateBalance(string $internetId, string $column, float $amount): bool
    {
        $validCols = ['checking_balance', 'savings_balance', 'loan_balance'];
        if (!in_array($column, $validCols, true)) return false;

        $db = Database::connect();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE accounts SET {$column} = {$column} + :amt WHERE internet_id = :iid");
            $stmt->execute(['amt' => $amount, 'iid' => $internetId]);
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('balance update failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    public static function verifyPin(array $user, string $pin): bool
    {
        return password_verify($pin, $user['pin_hash']);
    }

    public static function updatePassword(int $id, string $hashedPassword): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE accounts SET password_hash = :pw WHERE id = :id');
        return $stmt->execute(['pw' => $hashedPassword, 'id' => $id]);
    }

    public static function updatePin(int $id, string $hashedPin): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE accounts SET pin_hash = :pin WHERE id = :id');
        return $stmt->execute(['pin' => $hashedPin, 'id' => $id]);
    }
}
