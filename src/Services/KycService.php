<?php

declare(strict_types=1);

namespace Services;

use Models\Account;
use Helpers\Validate;
use Core\Database;

// kyc service

class KycService
{
    private static function uploadDir(): string
    {
        $dir = __DIR__ . '/../../uploads/kyc';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        return $dir;
    }

    // process upload
    private static function processUpload(array $file): array
    {
        $errors = Validate::fileUpload(
            $file,
            ['image/jpeg', 'image/png', 'application/pdf'],
            ['jpg', 'jpeg', 'png', 'pdf'],
            5242880
        );
        if (!empty($errors)) {
            return ['ok' => false, 'error' => implode(', ', $errors)];
        }

        $safeName = Validate::safeFilename($file['name']);
        $dest = self::uploadDir() . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => 'Failed to save file'];
        }

        return ['ok' => true, 'filename' => $safeName];
    }

    public static function submitKyc(
        string $internetId,
        array $idFront,
        array $idBack,
        string $idNumber,
        array $proofOfAddress
    ): array {
        $account = Account::findByInternetId($internetId);
        if (!$account) {
            return ['ok' => false, 'error' => 'Account not found'];
        }

        if (empty(trim($idNumber))) {
            return ['ok' => false, 'error' => 'ID number is required'];
        }

        // process files
        $front = self::processUpload($idFront);
        if (!$front['ok']) return ['ok' => false, 'error' => 'ID front: ' . $front['error']];

        $back = self::processUpload($idBack);
        if (!$back['ok']) return ['ok' => false, 'error' => 'ID back: ' . $back['error']];

        $proof = self::processUpload($proofOfAddress);
        if (!$proof['ok']) return ['ok' => false, 'error' => 'Proof of address: ' . $proof['error']];

        // update account
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE accounts SET id_front = :f, id_back = :b, id_number = :num, proof_of_address = :p, kyc_status = :s WHERE internet_id = :iid'
        );
        $ok = $stmt->execute([
            'f'   => $front['filename'],
            'b'   => $back['filename'],
            'num' => trim($idNumber),
            'p'   => $proof['filename'],
            's'   => 'pending',
            'iid' => $internetId,
        ]);

        return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'Failed to update account'];
    }

    public static function getStatus(string $internetId): ?string
    {
        $account = Account::findByInternetId($internetId);
        return $account ? ($account['kyc_status'] ?? 'none') : null;
    }

    public static function approve(string $internetId): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE accounts SET kyc_status = :s WHERE internet_id = :iid');
        return $stmt->execute(['s' => 'approved', 'iid' => $internetId]);
    }

    public static function reject(string $internetId): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE accounts SET kyc_status = :s WHERE internet_id = :iid');
        return $stmt->execute(['s' => 'rejected', 'iid' => $internetId]);
    }
}
