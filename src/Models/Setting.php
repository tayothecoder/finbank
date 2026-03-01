<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

// settings model

class Setting
{
    public static function get(): ?array
    {
        $db = Database::connect();
        $stmt = $db->query('SELECT * FROM settings WHERE id = 1');
        return $stmt->fetch() ?: null;
    }

    public static function update(array $data): bool
    {
        $allowed = ['site_name', 'site_email', 'site_phone', 'site_address',
                     'site_url', 'currency', 'wire_limit', 'domestic_limit',
                     'maintenance_mode'];
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }
        if (empty($sets)) return false;
        $db = Database::connect();
        $sql = 'UPDATE settings SET ' . implode(', ', $sets) . ' WHERE id = 1';
        return $db->prepare($sql)->execute($params);
    }
}
