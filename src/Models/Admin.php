<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// admin model

class Admin
{
    public static function findById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM admin WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM admin WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function verifyPassword(array $admin, string $password): bool
    {
        return password_verify($password, $admin['password_hash']);
    }

    public static function updateLastLogin(int $id): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE admin SET last_login = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
