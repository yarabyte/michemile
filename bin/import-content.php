#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Importe le contenu extrait de la maquette + redirections SEO WordPress.
 * Usage: php bin/import-content.php
 */

$root = dirname(__DIR__);
require $root . '/app/Support/helpers.php';
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
\App\Core\Env::load($root . '/.env');

use App\Core\Database;
use App\Models\Accommodation;
use App\Models\Restaurant;
use App\Models\SpaPage;
use App\Services\Settings;

$file = $root . '/resources/content/mockup-content.json';
if (!is_file($file)) {
    fwrite(STDERR, "Fichier manquant: resources/content/mockup-content.json\n");
    exit(1);
}
$data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

Settings::set('phone', '+237 653 29 02 58 / 222 20 86 71');
Settings::set('whatsapp', '237653290258');
Settings::set('address', 'Yaoundé, Bastos, à proximité de l\'Ambassade de Chine');
Settings::set('meta_title_fr', 'Les Résidences hôtelières Micheline & Emile — Yaoundé, Bastos');
Settings::set('meta_desc_fr', 'Suites, appartements et chambres à Yaoundé Bastos. Restaurants, spa, piscine, Wi-Fi et CANAL+.');
Settings::set('hero_image', '/assets/img/content/WhatsApp-Image-2026-06-23-at-11.33.48-960x1080.jpeg');

// Home sections
$introBody = '<p>Au cœur du mythique Quartier Bastos, le plus chic et glamour de la ville de Yaoundé, Les Résidences hôtelières Micheline et Emile vous proposent un cadre idéal pour votre séjour. Les Chambres, Appartements et Suites sont délicatement décorés. Son restaurant-bar-cabaret, son snack-bar ou encore son espace grillades/pizzeria, valent le détour. La piscine, son esplanade et ses cascades d\'eau achèveront de vous convaincre.</p>';
Database::query(
    'INSERT INTO home_sections (section_key, title_fr, title_en, body_fr, body_en, is_enabled, sort_order)
     VALUES (?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE title_fr=VALUES(title_fr), body_fr=VALUES(body_fr)',
    ['intro', 'Bienvenue chez vous !', 'Welcome home!', $introBody, '<p>Welcome to Micheline & Emile Residences in Bastos, Yaoundé.</p>', 1, 1]
);

$introMediaId = ensure_media_from_public_path(default_intro_image(), 'La résidence');
if ($introMediaId) {
    Database::query(
        'UPDATE home_sections SET media_id = ? WHERE section_key = ? AND (media_id IS NULL OR media_id = 0)',
        [$introMediaId, 'intro']
    );
}

Database::query('DELETE FROM accommodations');
$order = 0;
foreach ($data['rooms'] as $room) {
    $order++;
    $paras = $room['long'] ?? [];
    $body = '';
    foreach ($paras as $p) {
        $body .= '<p>' . htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    }
    $gallery = $room['gallery'] ?? [];
    if (!$gallery && !empty($room['img'])) $gallery = [$room['img']];
    Accommodation::save([
        'slug' => $room['slug'],
        'category' => $room['cat'],
        'name_fr' => $room['name'],
        'name_en' => $room['name'],
        'short_fr' => $room['short'] ?? '',
        'short_en' => $room['short'] ?? '',
        'body_fr' => $body,
        'body_en' => $body,
        'capacity' => (int) $room['cap'],
        'area_m2' => (int) $room['area'],
        'bedrooms' => (int) ($room['bedrooms'] ?? 1),
        'bathrooms' => (int) ($room['baths'] ?? 1),
        'beds' => $room['beds'] ?? null,
        'price_fcfa' => (int) $room['price'],
        'price_from' => !empty($room['from']) ? 1 : 0,
        'cover_media_id' => null,
        'practical_json' => json_encode($room['practical'] ?? [], JSON_UNESCAPED_UNICODE),
        'equipment_json' => json_encode($room['equip'] ?? [], JSON_UNESCAPED_UNICODE),
        'gallery_json' => json_encode($gallery, JSON_UNESCAPED_UNICODE),
        'meta_title_fr' => $room['name'],
        'meta_title_en' => $room['name'],
        'meta_desc_fr' => $room['short'] ?? null,
        'meta_desc_en' => $room['short'] ?? null,
        'is_published' => 1,
        'sort_order' => $order,
    ]);
}
echo "Hébergements: {$order}\n";

Database::query('DELETE FROM restaurants');
$legacyMap = [
    'bbl' => 'le-bleubird-lounge',
    'jdl' => 'les-jardins-de-lym-piscine',
    'dcc' => 'demgne-cofffee-club',
];
$cleanSlug = [
    'bbl' => 'le-bluebird-lounge',
    'jdl' => 'les-jardins-de-lym',
    'dcc' => 'demgne-coffee-club',
];
$order = 0;
foreach ($data['restaurants'] as $r) {
    $order++;
    $key = $r['slug'];
    Restaurant::save([
        'slug' => $cleanSlug[$key] ?? $key,
        'legacy_slug' => $legacyMap[$key] ?? null,
        'name_fr' => $r['name'],
        'name_en' => $r['name'],
        'abbr' => $r['abbr'] ?? null,
        'tagline_fr' => $r['tagline'] ?? null,
        'tagline_en' => $r['tagline'] ?? null,
        'body_fr' => '<p>' . htmlspecialchars($r['desc'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
        'body_en' => '<p>' . htmlspecialchars($r['desc'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
        'hours_fr' => $r['hours'] ?? null,
        'hours_en' => $r['hours'] ?? null,
        'specialties_json' => json_encode($r['specialties'] ?? [], JSON_UNESCAPED_UNICODE),
        'features_json' => json_encode($r['features'] ?? [], JSON_UNESCAPED_UNICODE),
        'cover_media_id' => null,
        'menu_media_id' => null,
        'image_url' => $r['img'] ?? null,
        'meta_title_fr' => $r['name'],
        'meta_title_en' => $r['name'],
        'meta_desc_fr' => $r['tagline'] ?? null,
        'meta_desc_en' => $r['tagline'] ?? null,
        'is_published' => 1,
        'sort_order' => $order,
    ]);
}
echo "Restaurants: {$order}\n";

Database::query('DELETE FROM spa_pages');
$spaLegacy = [
    'babys' => '/le-babys-spa-beauty-and-care/',
    'piscine' => '/la-piscine-des-jardins-de-lym/',
];
$order = 0;
foreach ($data['spa'] as $s) {
    $order++;
    $body = '';
    foreach ($s['paras'] ?? [] as $p) {
        $body .= '<p>' . htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    }
    SpaPage::save([
        'slug' => $s['slug'],
        'legacy_path' => $spaLegacy[$s['slug']] ?? null,
        'title_fr' => $s['title'],
        'title_en' => $s['title'],
        'lead_fr' => $s['lead'] ?? '',
        'lead_en' => $s['lead'] ?? '',
        'body_fr' => $body,
        'body_en' => $body,
        'blocks_json' => json_encode($s['blocks'] ?? [], JSON_UNESCAPED_UNICODE),
        'cta_label_fr' => $s['ctaLabel'] ?? null,
        'cta_label_en' => $s['ctaLabel'] ?? null,
        'cover_media_id' => null,
        'image_url' => $s['img'] ?? null,
        'meta_title_fr' => $s['title'],
        'meta_title_en' => $s['title'],
        'meta_desc_fr' => $s['lead'] ?? null,
        'meta_desc_en' => $s['lead'] ?? null,
        'is_published' => 1,
        'sort_order' => $order,
    ]);
}
echo "Pages spa: {$order}\n";

// Redirects for WP typos & taxonomies
$redirects = [
    ['/restaurants/le-bleubird-lounge/', '/restaurants/le-bluebird-lounge/', 301],
    ['/restaurants/demgne-cofffee-club/', '/restaurants/demgne-coffee-club/', 301],
    ['/restaurants/les-jardins-de-lym-piscine/', '/restaurants/les-jardins-de-lym/', 301],
    ['/type-de-chambre/suite/', '/nos-suites/', 301],
    ['/type-de-chambre/appartement/', '/nos-appartements/', 301],
    ['/type-de-chambre/chambre/', '/nos-chambres/', 301],
    ['/spa/spa/', '/spa/', 301],
];
foreach ($redirects as [$from, $to, $code]) {
    Database::query(
        'INSERT INTO redirects (from_path, to_path, status_code, is_active) VALUES (?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE to_path=VALUES(to_path), status_code=VALUES(status_code), is_active=1',
        [$from, $to, $code]
    );
}
echo "Redirections SEO importées.\n";
echo "OK.\n";
