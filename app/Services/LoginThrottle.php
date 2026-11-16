<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Env;

final class LoginThrottle
{
    public function isLocked(string $ip, string $email): bool
    {
        $max = (int) Env::get('LOGIN_MAX_ATTEMPTS', 5);
        $minutes = (int) Env::get('LOGIN_LOCKOUT_MINUTES', 15);
        $row = Database::fetch(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE ip_address = ? AND email = ? AND success = 0
               AND attempted_at >= (NOW() - INTERVAL ? MINUTE)',
            [$ip, $email, $minutes]
        );
        return ((int) ($row['c'] ?? 0)) >= $max;
    }

    public function record(string $ip, string $email, bool $success): void
    {
        Database::query(
            'INSERT INTO login_attempts (ip_address, email, success) VALUES (?, ?, ?)',
            [$ip, $email, $success ? 1 : 0]
        );
    }
}
