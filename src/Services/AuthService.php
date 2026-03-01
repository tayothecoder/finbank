<?php

declare(strict_types=1);

namespace Services;

use Core\{Database, Session, Security};
use Models\{Account, AuditLog};
use Helpers\Validate;

// auth service

class AuthService
{
    public static function login(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        $user = Validate::email($identifier)
            ? Account::findByEmail($identifier)
            : Account::findByInternetId($identifier);

        if (!$user || !Account::verifyPassword($user, $password)) {
            return ['ok' => false, 'error' => 'Invalid credentials'];
        }

        if ($user['status'] === 'blocked') {
            return ['ok' => false, 'error' => 'Account has been suspended'];
        }

        if ($user['status'] === 'hold') {
            return ['ok' => false, 'error' => 'Account is on hold. Contact support.'];
        }

        // set pending session
        Session::set('_pending_user', $user['id']);
        Session::set('_pending_iid', $user['internet_id']);
        Security::resetRateLimit('login');

        AuditLog::log($user['internet_id'], 'login_password', 'password verified, awaiting pin');

        return ['ok' => true, 'user' => $user, 'needs_pin' => true];
    }

    public static function verifyPin(string $pin): array
    {
        $userId = Session::get('_pending_user');
        if (!$userId) {
            return ['ok' => false, 'error' => 'No pending login'];
        }

        $user = Account::findById((int) $userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'Account not found'];
        }

        if (!Account::verifyPin($user, $pin)) {
            return ['ok' => false, 'error' => 'Invalid PIN'];
        }

        // full session
        Session::set('_pending_user', null);
        Session::set('_pending_iid', null);
        Session::setUser($user['id'], $user['internet_id']);
        Session::set('pin_verified', true);
        session_regenerate_id(true);

        AuditLog::log($user['internet_id'], 'login_complete', 'full login successful');

        return ['ok' => true, 'user' => $user];
    }

    public static function register(array $data): array
    {
        $errors = [];

        if (!Validate::required($data['first_name'] ?? '', 2, 100)) {
            $errors[] = 'First name is required';
        }
        if (!Validate::required($data['last_name'] ?? '', 2, 100)) {
            $errors[] = 'Last name is required';
        }
        if (!Validate::email($data['email'] ?? '')) {
            $errors[] = 'Valid email is required';
        }
        if (!Validate::required($data['password'] ?? '', 8, 255)) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors];
        }

        // check email
        if (Account::findByEmail($data['email'])) {
            return ['ok' => false, 'errors' => ['Email is already registered']];
        }

        $internetId = self::generateInternetId();
        $checkingAcct = self::generateAccountNumber();
        $savingsAcct = self::generateAccountNumber();

        // default pin 0000
        $id = Account::create([
            'internet_id'     => $internetId,
            'email'           => $data['email'],
            'password_hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
            'pin_hash'        => password_hash('0000', PASSWORD_DEFAULT),
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'phone'           => $data['phone'] ?? null,
            'checking_acct_no' => $checkingAcct,
            'savings_acct_no'  => $savingsAcct,
        ]);

        if (!$id) {
            return ['ok' => false, 'errors' => ['Registration failed. Try again.']];
        }

        AuditLog::log($internetId, 'register', 'new account created');

        return ['ok' => true, 'internet_id' => $internetId];
    }

    public static function forgotPassword(string $email): array
    {
        $user = Account::findByEmail($email);
        // prevent enumeration
        if (!$user) {
            return ['ok' => true];
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        Account::update($user['id'], [
            'reset_token' => $token,
            'reset_token_expires' => $expires,
        ]);

        AuditLog::log($user['internet_id'], 'password_reset_request', 'reset token generated');

        return ['ok' => true, 'token' => $token, 'user' => $user];
    }

    public static function resetPassword(string $token, string $newPassword): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT * FROM accounts WHERE reset_token = :tok AND reset_token_expires > NOW()'
        );
        $stmt->execute(['tok' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['ok' => false, 'error' => 'Invalid or expired reset link'];
        }

        if (mb_strlen($newPassword) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters'];
        }

        Account::updatePassword($user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
        Account::update($user['id'], ['reset_token' => null, 'reset_token_expires' => null]);

        AuditLog::log($user['internet_id'], 'password_reset', 'password was reset via token');

        return ['ok' => true];
    }

    public static function logout(): void
    {
        $user = Session::getUser();
        if ($user) {
            AuditLog::log($user['internet_id'], 'logout', 'user logged out');
        }
        Session::destroy();
    }

    private static function generateInternetId(): string
    {
        $db = Database::connect();
        do {
            $id = (string) random_int(100000000, 999999999);
            $stmt = $db->prepare('SELECT id FROM accounts WHERE internet_id = :iid');
            $stmt->execute(['iid' => $id]);
        } while ($stmt->fetch());
        return $id;
    }

    private static function generateAccountNumber(): string
    {
        return (string) random_int(1000000000, 9999999999);
    }
}
