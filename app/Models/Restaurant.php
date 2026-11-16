<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Restaurant
{
    public static function published(): array
    {
        return Database::fetchAll('SELECT * FROM restaurants WHERE is_published = 1 ORDER BY sort_order, id');
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM restaurants WHERE (slug = ? OR legacy_slug = ?) AND is_published = 1 LIMIT 1',
            [$slug, $slug]
        );
        return $row;
    }

    public static function allAdmin(): array
    {
        return Database::fetchAll('SELECT * FROM restaurants ORDER BY sort_order, id');
    }

    public static function save(array $data, ?int $id = null): int
    {
        $fields = [
            'slug','legacy_slug','name_fr','name_en','abbr','tagline_fr','tagline_en','body_fr','body_en',
            'hours_fr','hours_en','specialties_json','features_json','cover_media_id','menu_media_id','image_url',
            'meta_title_fr','meta_title_en','meta_desc_fr','meta_desc_en','is_published','sort_order'
        ];
        if ($id) {
            $sets = implode(', ', array_map(static fn ($f) => "$f = ?", $fields));
            $params = array_map(static fn ($f) => $data[$f] ?? null, $fields);
            $params[] = $id;
            Database::query("UPDATE restaurants SET $sets WHERE id = ?", $params);
            return $id;
        }
        $cols = implode(', ', $fields);
        $ph = implode(', ', array_fill(0, count($fields), '?'));
        $params = array_map(static fn ($f) => $data[$f] ?? null, $fields);
        return (int) Database::insert("INSERT INTO restaurants ($cols) VALUES ($ph)", $params);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM restaurants WHERE id = ?', [$id]);
    }
}
