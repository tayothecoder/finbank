<?php

declare(strict_types=1);

namespace Core;

// session handling

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = (int) Env::get('SESSION_LIFETIME', '1800');

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly'  => true,
            'samesite' => 'Strict',
        ]);

        session_name('offshore_sid');
        session_start();
        self::$started = true;

        // timeout check
        if (isset($_SESSION['_last_activity'])) {
            $elapsed = time() - $_SESSION['_last_activity'];
            if ($elapsed > $lifetime) {
                self::destroy();
                self::start();
                return;
            }
        }

        $_SESSION['_last_activity'] = time();

        // regen id
        $regenInterval = 900;
        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        } elseif (time() - $_SESSION['_created_at'] > $regenInterval) {
            session_regenerate_id(true);
            $_SESSION['_created_at'] = time();
        }
    }

    // admin session ns
    public static function setAdmin(int $adminId, string $email): void
    {
        $_SESSION['_admin'] = [
            'id'    => $adminId,
            'email' => $email,
            'time'  => time(),
        ];
    }

    public static function getAdmin(): ?array
    {
        return $_SESSION['_admin'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['_admin']['id']);
    }

    public static function setUser(int $userId, string $internetId): void
    {
        $_SESSION['_user'] = [
            'id'          => $userId,
            'internet_id' => $internetId,
            'time'        => time(),
        ];
    }

    public static function getUser(): ?array
    {
        return $_SESSION['_user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['_user']['id']);
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
        self::$started = false;
    }
}
