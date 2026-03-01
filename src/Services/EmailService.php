<?php

declare(strict_types=1);

namespace Services;

use Core\{Database, Env};

// email service

class EmailService
{
    private static function getSmtpSettings(): array
    {
        $db = Database::connect();
        $stmt = $db->query('SELECT * FROM smtp_settings WHERE id = 1');
        $row = $stmt->fetch();
        if ($row) return $row;

        // fallback
        return [
            'host'       => Env::get('SMTP_HOST'),
            'port'       => (int) Env::get('SMTP_PORT', '587'),
            'username'   => Env::get('SMTP_USER'),
            'password'   => Env::get('SMTP_PASS'),
            'from_email' => Env::get('SMTP_FROM'),
            'from_name'  => Env::get('SMTP_FROM_NAME', 'Offshore Bank'),
            'encryption' => Env::get('SMTP_ENCRYPTION', 'tls'),
        ];
    }

    private static function send(string $to, string $subject, string $body): bool
    {
        $smtp = self::getSmtpSettings();
        $phpmailerPath = dirname(__DIR__, 2) . '/vendor/phpmailer/phpmailer/src';

        require_once $phpmailerPath . '/Exception.php';
        require_once $phpmailerPath . '/PHPMailer.php';
        require_once $phpmailerPath . '/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->Port       = (int) $smtp['port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'] === 'ssl' ? 'ssl' : 'tls';

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::wrapTemplate($subject, $body);
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('email send failed: ' . $e->getMessage());
            return false;
        }
    }

    // html template
    private static function wrapTemplate(string $title, string $content): string
    {
        $appName = Env::get('APP_NAME', 'Offshore Private Union Bank');
        $year = date('Y');
        return <<<HTML
        <!DOCTYPE html><html><head><meta charset="utf-8">
        <style>body{margin:0;padding:0;font-family:-apple-system,sans-serif;background:#f5f3ff;color:#1e0e62}
        .wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden}
        .head{background:#1e0e62;padding:24px 32px;color:#fff;font-size:18px;font-weight:500}
        .body{padding:32px}p{margin:0 0 16px;line-height:1.6;font-size:15px}
        .btn{display:inline-block;background:#1e0e62;color:#fff;padding:12px 28px;border-radius:50px;
        text-decoration:none;font-weight:500;font-size:14px}
        .foot{padding:24px 32px;text-align:center;font-size:12px;color:#888}</style></head>
        <body><div class="wrap"><div class="head">{$appName}</div>
        <div class="body">{$content}</div>
        <div class="foot">{$appName} - {$year}. All rights reserved.</div></div></body></html>
        HTML;
    }

    public static function sendWelcome(string $to, string $firstName, string $internetId): bool
    {
        $body = "<p>Hello {$firstName},</p>
        <p>Welcome to Offshore Private Union Bank. Your account has been created.</p>
        <p>Your Internet Banking ID: <strong>{$internetId}</strong></p>
        <p>Your default PIN is <strong>0000</strong>. Please change it after your first login.</p>";
        return self::send($to, 'Welcome to Offshore Private Union Bank', $body);
    }

    public static function sendLoginAlert(string $to, string $firstName): bool
    {
        $time = date('M d, Y h:i A');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $body = "<p>Hello {$firstName},</p>
        <p>A new login was detected on your account.</p>
        <p>Time: {$time}<br>IP: {$ip}</p>
        <p>If this was not you, contact support immediately.</p>";
        return self::send($to, 'Login Alert', $body);
    }

    public static function sendPasswordReset(string $to, string $firstName, string $link): bool
    {
        $body = "<p>Hello {$firstName},</p>
        <p>You requested a password reset. Click the link below to set a new password.</p>
        <p><a href=\"{$link}\" class=\"btn\">Reset Password</a></p>
        <p>This link expires in 1 hour. If you did not request this, ignore this email.</p>";
        return self::send($to, 'Password Reset Request', $body);
    }

    public static function sendTransactionNotification(
        string $to, string $firstName, string $type, string $amount, string $status
    ): bool {
        $body = "<p>Hello {$firstName},</p>
        <p>Your {$type} transaction of <strong>{$amount}</strong> is now <strong>{$status}</strong>.</p>
        <p>Log in to your account to view details.</p>";
        return self::send($to, 'Transaction ' . ucfirst($status), $body);
    }

    public static function sendPinCode(string $to, string $firstName, string $pin): bool
    {
        $body = "<p>Hello {$firstName},</p>
        <p>Your verification PIN is: <strong>{$pin}</strong></p>
        <p>Do not share this code with anyone.</p>";
        return self::send($to, 'Your Verification PIN', $body);
    }

}
