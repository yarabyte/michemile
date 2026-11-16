<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SpaPage
{
    public static function published(): array
    {
        return Database::fetchAll('SELECT * FROM spa_pages WHERE is_published = 1 ORDER BY sort_order, id');
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM spa_pages WHERE slug = ? AND is_published = 1 LIMIT 1', [$slug]);
    }

    public static function findByLegacyPath(string $path): ?array
    {
        return Database::fetch('SELECT * FROM spa_pages WHERE legacy_path = ? AND is_published = 1 LIMIT 1', [$path]);
    }

    public static function allAdmin(): array
    {
        return Database::fetchAll('SELECT * FROM spa_pages ORDER BY sort_order, id');
    }

    public static function save(array $data, ?int $id = null): int
    {
        $fields = [
            'slug','legacy_path','title_fr','title_en','lead_fr','lead_en','body_fr','body_en','blocks_json',
            'cta_label_fr','cta_label_en','cover_media_id','image_url','meta_title_fr','meta_title_en','meta_desc_fr',
            'meta_desc_en','is_published','sort_order'
        ];
        if ($id) {
            $sets = implode(', ', array_map(static fn ($f) => "$f = ?", $fields));
            $params = array_map(static fn ($f) => $data[$f] ?? null, $fields);
            $params[] = $id;
            Database::query("UPDATE spa_pages SET $sets WHERE id = ?", $params);
            return $id;
        }
        $cols = implode(', ', $fields);
        $ph = implode(', ', array_fill(0, count($fields), '?'));
        $params = array_map(static fn ($f) => $data[$f] ?? null, $fields);
        return (int) Database::insert("INSERT INTO spa_pages ($cols) VALUES ($ph)", $params);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM spa_pages WHERE id = ?', [$id]);
    }
}
