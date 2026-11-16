<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Accommodation
{
    public static function published(?string $category = null): array
    {
        if ($category) {
            return Database::fetchAll(
                'SELECT * FROM accommodations WHERE is_published = 1 AND category = ? ORDER BY sort_order, id',
                [$category]
            );
        }
        return Database::fetchAll(
            'SELECT * FROM accommodations WHERE is_published = 1 ORDER BY sort_order, id'
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT * FROM accommodations WHERE slug = ? AND is_published = 1 LIMIT 1',
            [$slug]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM accommodations WHERE id = ? LIMIT 1', [$id]);
    }

    public static function allAdmin(): array
    {
        return Database::fetchAll('SELECT * FROM accommodations ORDER BY sort_order, id');
    }

    public static function save(array $data, ?int $id = null): int
    {
        $fields = [
            'slug','category','name_fr','name_en','short_fr','short_en','body_fr','body_en',
            'capacity','area_m2','bedrooms','bathrooms','beds','price_fcfa','price_from',
            'cover_media_id','practical_json','equipment_json','gallery_json',
            'meta_title_fr','meta_title_en','meta_desc_fr','meta_desc_en','is_published','sort_order'
        ];
        if ($id) {
            $sets = implode(', ', array_map(static fn ($f) => "$f = ?", $fields));
            $params = array_map(static fn ($f) => $data[$f] ?? null, $fields);
            $params[] = $id;
            Database::query("UPDATE accommodations SET $sets WHERE id = ?", $params);
            return $id;
        }
        $cols = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $params = array_map(static fn ($f) => $data[$f] ?? null, $fields);
        return (int) Database::insert("INSERT INTO accommodations ($cols) VALUES ($placeholders)", $params);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM accommodations WHERE id = ?', [$id]);
    }

    /**
     * @param list<int|string> $ids Ordered list of accommodation ids
     */
    public static function reorder(array $ids): void
    {
        $order = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $order += 10;
            Database::query('UPDATE accommodations SET sort_order = ? WHERE id = ?', [$order, $id]);
        }
    }
}
