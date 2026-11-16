<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Booking
{
    public static function create(array $data): array
    {
        $ref = self::reference();
        $id = Database::insert(
            'INSERT INTO bookings
            (reference, accommodation_id, status, guest_name, guest_email, guest_phone, check_in, check_out, guests, message, locale, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $ref,
                $data['accommodation_id'] ?? null,
                'new',
                $data['guest_name'],
                $data['guest_email'],
                $data['guest_phone'],
                $data['check_in'],
                $data['check_out'],
                $data['guests'],
                $data['message'] ?? null,
                $data['locale'] ?? 'fr',
                $data['ip_address'] ?? null,
                $data['user_agent'] ?? null,
            ]
        );
        return self::find((int) $id);
    }

    public static function find(int $id): array
    {
        return Database::fetch('SELECT b.*, a.name_fr AS room_name, a.name_en AS room_name_en, a.slug AS room_slug
            FROM bookings b LEFT JOIN accommodations a ON a.id = b.accommodation_id WHERE b.id = ?', [$id]) ?? [];
    }

    public static function findByReference(string $ref): ?array
    {
        return Database::fetch('SELECT b.*, a.name_fr AS room_name, a.name_en AS room_name_en FROM bookings b
            LEFT JOIN accommodations a ON a.id = b.accommodation_id WHERE b.reference = ?', [$ref]);
    }

    public static function allAdmin(?string $status = null): array
    {
        if ($status) {
            return Database::fetchAll(
                'SELECT b.*, a.name_fr AS room_name, a.name_en AS room_name_en FROM bookings b
                 LEFT JOIN accommodations a ON a.id = b.accommodation_id
                 WHERE b.status = ? ORDER BY b.created_at DESC',
                [$status]
            );
        }
        return Database::fetchAll(
            'SELECT b.*, a.name_fr AS room_name, a.name_en AS room_name_en FROM bookings b
             LEFT JOIN accommodations a ON a.id = b.accommodation_id
             ORDER BY b.created_at DESC LIMIT 500'
        );
    }

    /** @return array{all:int,new:int,confirmed:int,cancelled:int,completed:int} */
    public static function counts(): array
    {
        $rows = Database::fetchAll('SELECT status, COUNT(*) AS c FROM bookings GROUP BY status');
        $out = ['all' => 0, 'new' => 0, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0];
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

    public static function updateStatus(int $id, string $status, ?string $notes = null): void
    {
        Database::query(
            'UPDATE bookings SET status = ?, admin_notes = ? WHERE id = ?',
            [$status, $notes, $id]
        );
    }

    private static function reference(): string
    {
        return 'ME' . strtoupper(bin2hex(random_bytes(4)));
    }
}
