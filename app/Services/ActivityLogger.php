<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ActivityLogger
{
    public static function log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        Database::query(
            'INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, meta_json)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $action,
                $entityType,
                $entityId,
                client_ip(),
                $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }
}
