<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Message
{
    public static function create(array $data): int
    {
        return (int) Database::insert(
            'INSERT INTO messages (name, email, phone, subject, body, locale, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'], $data['email'], $data['phone'] ?? null, $data['subject'] ?? null,
                $data['body'], $data['locale'] ?? 'fr', $data['ip_address'] ?? null,
            ]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM messages WHERE id = ?', [$id]);
    }

    public static function allAdmin(?string $status = null): array
    {
        if ($status) {
            return Database::fetchAll('SELECT * FROM messages WHERE status = ? ORDER BY created_at DESC', [$status]);
        }
        return Database::fetchAll('SELECT * FROM messages ORDER BY created_at DESC LIMIT 500');
    }

    /** @return array{all:int,new:int,read:int,archived:int} */
    public static function counts(): array
    {
        $rows = Database::fetchAll('SELECT status, COUNT(*) AS c FROM messages GROUP BY status');
        $out = ['all' => 0, 'new' => 0, 'read' => 0, 'archived' => 0];
        foreach ($rows as $row) {
            $st = (string) ($row['status'] ?? '');
            $c = (int) ($row['c'] ?? 0);
            $out['all'] += $c;
            if (isset($out[$st])) {
                $out[$st] = $c;
            }
        }
        return $out;
    }

    public static function mark(int $id, string $status): void
    {
        Database::query('UPDATE messages SET status = ? WHERE id = ?', [$status, $id]);
    }
}
