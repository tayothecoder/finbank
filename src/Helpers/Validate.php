<?php

declare(strict_types=1);

namespace Helpers;

// validation helpers

class Validate
{
    // sanitize string
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    // email check
    public static function email(string $email): bool
    {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    // internet id format check
    public static function internetId(string $id): bool
    {
        $id = trim($id);
        return preg_match('/^\d{6,20}$/', $id) === 1;
    }

    // int validation w/ optional range
    public static function integer(mixed $value, int $min = null, int $max = null): bool
    {
        if (!is_numeric($value) || (int) $value != $value) {
            return false;
        }

        $intVal = (int) $value;

        if ($min !== null && $intVal < $min) {
            return false;
        }

        if ($max !== null && $intVal > $max) {
            return false;
        }

        return true;
    }

    // money amount
    public static function amount(mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return (float) $value > 0;
    }

    // file upload validation
    public static function fileUpload(
        array $file,
        array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
        array $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
        int $maxSize = 5242880
    ): array {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'file upload failed with error code ' . $file['error'];
            return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'file exceeds maximum size of ' . round($maxSize / 1048576, 1) . 'MB';
        }

        // mime check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes, true)) {
            $errors[] = 'file type not allowed: ' . $mimeType;
        }

        // ext check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $errors[] = 'file extension not allowed: ' . $ext;
        }

        return $errors;
    }

    // random filename
    public static function safeFilename(string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return bin2hex(random_bytes(16)) . '.' . $ext;
    }

    // required string check
    public static function required(string $value, int $minLen = 1, int $maxLen = 500): bool
    {
        $len = mb_strlen(trim($value));
        return $len >= $minLen && $len <= $maxLen;
    }
}
