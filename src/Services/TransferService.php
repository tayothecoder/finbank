<?php

declare(strict_types=1);

namespace Services;

use Core\Database;
use Models\{Account, AuditLog, Setting, Transaction};

// transfer logic

class TransferService
{
    public static function generateReference(): string
    {
        return 'TXN' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    public static function validateTransfer(string $internetId, float $amount, string $paymentAccount): array
    {
        $user = Account::findByInternetId($internetId);
        if (!$user) return ['ok' => false, 'error' => 'Account not found'];
        if ($user['status'] !== 'active') return ['ok' => false, 'error' => 'Account is not active'];
        if (!$user['transfer_enabled']) return ['ok' => false, 'error' => 'Transfers are disabled on this account'];
        if ($amount <= 0) return ['ok' => false, 'error' => 'Amount must be greater than zero'];
        $balanceCol = $paymentAccount === 'savings' ? 'savings_balance' : 'checking_balance';
        $balance = (float) $user[$balanceCol];
        if ($amount > $balance) return ['ok' => false, 'error' => 'Insufficient balance'];
        return ['ok' => true, 'user' => $user, 'balance' => $balance];
    }

    public static function domesticTransfer(array $data): array
    {
        $internetId = $data['internet_id'];
        $amount = (float) $data['amount'];
        $paymentAccount = $data['payment_account'];

        $validation = self::validateTransfer($internetId, $amount, $paymentAccount);
        if (!$validation['ok']) return $validation;

        // limit check
        $limit = (float) (Setting::get()['domestic_limit'] ?? 10000);
        if ($amount > $limit) return ['ok' => false, 'error' => 'Amount exceeds domestic transfer limit'];

        $balanceCol = $paymentAccount === 'savings' ? 'savings_balance' : 'checking_balance';
        $ref = self::generateReference();
        $db = Database::connect();

        try {
            $db->beginTransaction();

            // deduct
            $stmt = $db->prepare("UPDATE accounts SET {$balanceCol} = {$balanceCol} - :amt WHERE internet_id = :iid");
            $stmt->execute(['amt' => $amount, 'iid' => $internetId]);

            // create txn
            $txnId = Transaction::create([
                'internet_id'       => $internetId,
                'type'              => 'domestic',
                'amount'            => $amount,
                'fee'               => 0,
                'currency'          => $validation['user']['currency'] ?? 'USD',
                'status'            => 'completed',
                'reference_id'      => $ref,
                'description'       => $data['description'] ?? 'Domestic transfer',
                'payment_account'   => $paymentAccount,
                'recipient_name'    => $data['recipient_name'],
                'recipient_account' => $data['recipient_account'],
                'recipient_country' => $data['bank_country'] ?? null,
                'trans_type'        => 'debit',
            ]);

            $db->commit();

            AuditLog::log($internetId, 'domestic_transfer', "amount: {$amount}, ref: {$ref}");
            self::notifyUser($validation['user'], 'domestic', $amount);

            return ['ok' => true, 'transaction_id' => $txnId, 'reference_id' => $ref];
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('domestic transfer failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Transfer failed. Please try again.'];
        }
    }

    public static function wireTransfer(array $data): array
    {
        $internetId = $data['internet_id'];
        $amount = (float) $data['amount'];
        $fee = (float) ($data['fee'] ?? 25.00);
        $total = $amount + $fee;
        $paymentAccount = $data['payment_account'];

        $validation = self::validateTransfer($internetId, $total, $paymentAccount);
        if (!$validation['ok']) return $validation;

        $settings = Setting::get();
        $limit = (float) ($settings['wire_limit'] ?? 50000);
        if ($amount > $limit) {
            return ['ok' => false, 'error' => 'Amount exceeds wire transfer limit of ' . number_format($limit, 2)];
        }

        $balanceCol = $paymentAccount === 'savings' ? 'savings_balance' : 'checking_balance';
        $ref = self::generateReference();
        $db = Database::connect();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE accounts SET {$balanceCol} = {$balanceCol} - :amt WHERE internet_id = :iid");
            $stmt->execute(['amt' => $total, 'iid' => $internetId]);

            $txnId = Transaction::create([
                'internet_id'       => $internetId,
                'type'              => 'wire',
                'amount'            => $amount,
                'fee'               => $fee,
                'currency'          => $validation['user']['currency'] ?? 'USD',
                'status'            => 'completed',
                'reference_id'      => $ref,
                'description'       => $data['description'] ?? 'Wire transfer',
                'payment_account'   => $paymentAccount,
                'recipient_name'    => $data['recipient_name'],
                'recipient_account' => $data['recipient_account'],
                'recipient_bank'    => $data['bank_name'] ?? null,
                'recipient_country' => $data['bank_country'] ?? null,
                'swift_code'        => $data['swift_code'] ?? null,
                'bank_address'      => $data['bank_address'] ?? null,
                'trans_type'        => 'debit',
            ]);

            $db->commit();

            AuditLog::log($internetId, 'wire_transfer', "amount: {$amount}, fee: {$fee}, ref: {$ref}");
            self::notifyUser($validation['user'], 'wire', $amount);

            return ['ok' => true, 'transaction_id' => $txnId, 'reference_id' => $ref];
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('wire transfer failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Transfer failed. Please try again.'];
        }
    }

    public static function selfTransfer(array $data): array
    {
        $internetId = $data['internet_id'];
        $amount = (float) $data['amount'];
        $fromAccount = $data['from_account'];
        $toAccount = $data['to_account'];

        if ($fromAccount === $toAccount) return ['ok' => false, 'error' => 'Source and destination must differ'];
        $validation = self::validateTransfer($internetId, $amount, $fromAccount);
        if (!$validation['ok']) return $validation;

        $fromCol = $fromAccount === 'savings' ? 'savings_balance' : 'checking_balance';
        $toCol = $toAccount === 'savings' ? 'savings_balance' : 'checking_balance';
        $ref = self::generateReference();
        $db = Database::connect();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE accounts SET {$fromCol} = {$fromCol} - :amt, {$toCol} = {$toCol} + :amt2 WHERE internet_id = :iid");
            $stmt->execute(['amt' => $amount, 'amt2' => $amount, 'iid' => $internetId]);

            $txnId = Transaction::create([
                'internet_id'     => $internetId,
                'type'            => 'self',
                'amount'          => $amount,
                'currency'        => $validation['user']['currency'] ?? 'USD',
                'status'          => 'completed',
                'reference_id'    => $ref,
                'description'     => $data['description'] ?? "Transfer from {$fromAccount} to {$toAccount}",
                'payment_account' => $fromAccount,
                'trans_type'      => 'debit',
            ]);

            $db->commit();

            AuditLog::log($internetId, 'self_transfer', "amount: {$amount}, {$fromAccount} -> {$toAccount}, ref: {$ref}");
            self::notifyUser($validation['user'], 'self', $amount);

            return ['ok' => true, 'transaction_id' => $txnId, 'reference_id' => $ref];
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('self transfer failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Transfer failed. Please try again.'];
        }
    }

    private static function notifyUser(array $user, string $type, float $amount): void
    {
        $formatted = \Helpers\Format::currency($amount, $user['currency'] ?? 'USD');
        EmailService::sendTransactionNotification(
            $user['email'], $user['first_name'], $type, $formatted, 'completed'
        );
    }
}
