<?php

declare(strict_types=1);

namespace Services;

use Models\Card;
use Models\Account;
use Models\Transaction;

// card service

class CardService
{
    private const CARD_FEE = 10.00;

    // generate card number
    private static function generateCardNumber(string $type): string
    {
        $prefix = $type === 'visa' ? '4' : '5';
        $number = $prefix;
        for ($i = 0; $i < 15; $i++) {
            $number .= random_int(0, 9);
        }
        return $number;
    }

    private static function generateCvv(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    public static function requestCard(string $internetId, string $cardType, string $cardName): array
    {
        $validTypes = ['visa', 'mastercard'];
        if (!in_array($cardType, $validTypes, true)) {
            return ['ok' => false, 'error' => 'Invalid card type'];
        }

        if (empty(trim($cardName))) {
            return ['ok' => false, 'error' => 'Card name is required'];
        }

        $account = Account::findByInternetId($internetId);
        if (!$account) {
            return ['ok' => false, 'error' => 'Account not found'];
        }

        if ((float) $account['checking_balance'] < self::CARD_FEE) {
            return ['ok' => false, 'error' => 'Insufficient balance for card fee ($' . number_format(self::CARD_FEE, 2) . ')'];
        }

        $cvv = self::generateCvv();
        $cardNumber = self::generateCardNumber($cardType);
        $expiry = date('m/Y', strtotime('+3 years'));

        $cardId = Card::create([
            'internet_id' => $internetId,
            'card_number' => $cardNumber,
            'card_name'   => strtoupper(trim($cardName)),
            'card_type'   => $cardType,
            'expiry_date' => $expiry,
            'cvv_hash'    => password_hash($cvv, PASSWORD_DEFAULT),
            'status'      => 'pending',
            'fee'         => self::CARD_FEE,
        ]);

        if (!$cardId) {
            return ['ok' => false, 'error' => 'Failed to create card'];
        }

        // fee
        Account::updateBalance($internetId, 'checking_balance', -self::CARD_FEE);

        // log txn
        Transaction::create([
            'internet_id'   => $internetId,
            'type'          => 'card',
            'amount'        => self::CARD_FEE,
            'fee'           => 0,
            'status'        => 'completed',
            'reference_id'  => 'CARD-' . strtoupper(bin2hex(random_bytes(6))),
            'description'   => ucfirst($cardType) . ' card request fee',
            'trans_type'    => 'debit',
        ]);

        return ['ok' => true, 'card_id' => $cardId, 'cvv' => $cvv];
    }

    public static function activateCard(int $cardId): bool
    {
        return Card::updateStatus($cardId, 'active');
    }

    public static function freezeCard(int $cardId): bool
    {
        return Card::updateStatus($cardId, 'frozen');
    }

    public static function cancelCard(int $cardId): bool
    {
        return Card::updateStatus($cardId, 'cancelled');
    }
}
