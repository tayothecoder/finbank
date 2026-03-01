<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

// db connection

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME');
        $user = Env::get('DB_USER');
        $pass = Env::get('DB_PASS');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ]);
        } catch (PDOException $e) {
            // log err
            error_log('database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('database connection failed');
        }

        return self::$instance;
    }

    // reset (testing)
    public static function reset(): void
    {
        self::$instance = null;
    }
}
