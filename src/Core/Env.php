<?php

declare(strict_types=1);

namespace Core;

// env loader

// autoloader
spl_autoload_register(function (string $class) {
    $srcDir = dirname(__DIR__); // /banking/src
    $namespaces = ['Core', 'Services', 'Models', 'Helpers'];
    foreach ($namespaces as $ns) {
        if (str_starts_with($class, $ns . '\\')) {
            $file = $srcDir . '/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

class Env
{
    private static bool $loaded = false;

    public static function load(string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path ??= dirname(__DIR__, 2) . '/.env';

        if (!file_exists($path)) {
            throw new \RuntimeException('env file not found: ' . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // skip
            if (str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // strip quotes
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $m)) {
                $value = $m[2];
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        self::defineConstants();
        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    private static function defineConstants(): void
    {
        $map = [
            'APP_NAME', 'APP_URL', 'APP_ENV', 'APP_DEBUG',
            'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
            'SESSION_LIFETIME', 'RATE_LIMIT_WINDOW', 'RATE_LIMIT_MAX',
        ];

        foreach ($map as $key) {
            if (!defined($key) && isset($_ENV[$key])) {
                define($key, $_ENV[$key]);
            }
        }
    }
}
