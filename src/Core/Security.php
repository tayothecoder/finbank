<?php

declare(strict_types=1);

namespace Core;

// csrf, rate limiting, headers

class Security
{
    // get/create csrf token
    public static function csrfToken(): string
    {
        Session::start();

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    // hidden csrf input
    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    // verify csrf
    public static function verifyCsrf(string $token = null): bool
    {
        Session::start();
        $token ??= $_POST['_csrf_token'] ?? '';
        $expected = $_SESSION['_csrf_token'] ?? '';

        if (empty($expected) || empty($token)) {
            return false;
        }

        $valid = hash_equals($expected, $token);

        // rotate
        unset($_SESSION['_csrf_token']);

        return $valid;
    }

    // rate limit check
    public static function checkRateLimit(
        string $action,
        string $ip = null
    ): bool {
        $ip ??= $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $window = (int) Env::get('RATE_LIMIT_WINDOW', '300');
        $maxAttempts = (int) Env::get('RATE_LIMIT_MAX', '5');

        $db = Database::connect();

        // cleanup old
        $stmt = $db->prepare(
            'DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL :window SECOND)'
        );
        $stmt->execute(['window' => $window]);

        // check attempts
        $stmt = $db->prepare(
            'SELECT attempts, window_start FROM rate_limits WHERE ip_address = :ip AND action = :action'
        );
        $stmt->execute(['ip' => $ip, 'action' => $action]);
        $row = $stmt->fetch();

        if (!$row) {
            // new entry
            $stmt = $db->prepare(
                'INSERT INTO rate_limits (ip_address, action, attempts, window_start) VALUES (:ip, :action, 1, NOW())'
            );
            $stmt->execute(['ip' => $ip, 'action' => $action]);
            return true;
        }

        if ($row['attempts'] >= $maxAttempts) {
            return false;
        }

        // increment
        $stmt = $db->prepare(
            'UPDATE rate_limits SET attempts = attempts + 1 WHERE ip_address = :ip AND action = :action'
        );
        $stmt->execute(['ip' => $ip, 'action' => $action]);

        return true;
    }

    // clear rate limit
    public static function resetRateLimit(string $action, string $ip = null): void
    {
        $ip ??= $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db = Database::connect();
        $stmt = $db->prepare('DELETE FROM rate_limits WHERE ip_address = :ip AND action = :action');
        $stmt->execute(['ip' => $ip, 'action' => $action]);
    }

    // security headers
    public static function headers(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
}
