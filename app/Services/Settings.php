<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class Settings
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $stored = is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_UNICODE);
        Database::query(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $stored]
        );
        self::$cache = null;
    }

    public static function all(): array
    {
        self::load();
        return self::$cache ?? [];
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
    }
}
