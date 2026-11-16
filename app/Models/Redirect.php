<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Redirect
{
    public static function find(string $path): ?array
    {
        if ($path !== '/') {
            $path = rtrim($path, '/') . '/';
        }
        return Database::fetch('SELECT * FROM redirects WHERE from_path = ? AND is_active = 1 LIMIT 1', [$path]);
    }
}
