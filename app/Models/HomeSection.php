<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class HomeSection
{
    public static function enabled(): array
    {
        return Database::fetchAll('SELECT * FROM home_sections WHERE is_enabled = 1 ORDER BY sort_order, id');
    }

    public static function allAdmin(): array
    {
        return Database::fetchAll('SELECT * FROM home_sections ORDER BY sort_order, id');
    }

    public static function byKey(string $key): ?array
    {
        return Database::fetch('SELECT * FROM home_sections WHERE section_key = ? LIMIT 1', [$key]);
    }

    public static function mapByKey(?array $sections = null): array
    {
        $rows = $sections ?? self::enabled();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['section_key']] = $row;
        }
        return $map;
    }

    public static function extra(?array $section): array
    {
        if (!$section) {
            return [];
        }
        $data = json_decode((string) ($section['extra_json'] ?? ''), true);
        return is_array($data) ? $data : [];
    }

    public static function save(array $data, int $id): void
    {
        Database::query(
            'UPDATE home_sections SET title_fr=?, title_en=?, subtitle_fr=?, subtitle_en=?, body_fr=?, body_en=?, media_id=?, extra_json=?, is_enabled=?, sort_order=? WHERE id=?',
            [
                $data['title_fr'] ?? null, $data['title_en'] ?? null, $data['subtitle_fr'] ?? null, $data['subtitle_en'] ?? null,
                $data['body_fr'] ?? null, $data['body_en'] ?? null, $data['media_id'] ?? null, $data['extra_json'] ?? null,
                $data['is_enabled'] ?? 1, $data['sort_order'] ?? 0, $id,
            ]
        );
    }

    /** Ensure homepage sections exist with sensible defaults for the secretary UI. */
    public static function ensureDefaults(): void
    {
        $defaults = self::defaultDefinitions();
        foreach ($defaults as $def) {
            $existing = self::byKey($def['section_key']);
            if ($existing) {
                continue;
            }
            Database::insert(
                'INSERT INTO home_sections (section_key, title_fr, title_en, subtitle_fr, subtitle_en, body_fr, body_en, extra_json, is_enabled, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                [
                    $def['section_key'],
                    $def['title_fr'] ?? null,
                    $def['title_en'] ?? null,
                    $def['subtitle_fr'] ?? null,
                    $def['subtitle_en'] ?? null,
                    $def['body_fr'] ?? null,
                    $def['body_en'] ?? null,
                    isset($def['extra']) ? json_encode($def['extra'], JSON_UNESCAPED_UNICODE) : null,
                    1,
                    $def['sort_order'] ?? 0,
                ]
            );
        }

        // Enrich existing intro with quote if missing
        $intro = self::byKey('intro');
        if ($intro) {
            $extra = self::extra($intro);
            $changed = false;
            if (empty($extra['quote_fr'])) {
                $extra['quote_fr'] = 'Suites, appartements et chambres délicatement décorés, restaurants, spa et piscine aux cascades.';
                $changed = true;
            }
            if (empty($extra['quote_en'])) {
                $extra['quote_en'] = 'Suites, apartments and rooms, restaurants, spa and cascading pool.';
                $changed = true;
            }
            if (isset($extra['eyebrow_fr']) || isset($extra['eyebrow_en'])) {
                unset($extra['eyebrow_fr'], $extra['eyebrow_en']);
                $changed = true;
            }
            if ($changed) {
                Database::query('UPDATE home_sections SET extra_json=? WHERE id=?', [
                    json_encode($extra, JSON_UNESCAPED_UNICODE),
                    (int) $intro['id'],
                ]);
            }
        }
    }

    public static function defaultDefinitions(): array
    {
        return [
            [
                'section_key' => 'hero',
                'sort_order' => 10,
                'title_fr' => 'Micheline & Emile',
                'title_en' => 'Micheline & Emile',
                'subtitle_fr' => 'Au cœur du quartier résidentiel et diplomatique le plus chic de Yaoundé — suites, restaurants, spa et piscine.',
                'subtitle_en' => 'In the heart of Yaoundé’s most exclusive residential district — suites, restaurants, spa and pool.',
                'extra' => [],
            ],
            [
                'section_key' => 'intro',
                'sort_order' => 20,
                'title_fr' => 'Bienvenue chez vous !',
                'title_en' => 'Welcome home!',
                'body_fr' => '<p>Au cœur du mythique Quartier Bastos, Les Résidences hôtelières Micheline et Emile vous proposent un cadre idéal pour votre séjour.</p>',
                'body_en' => '<p>Welcome to Micheline & Emile Residences in Bastos, Yaoundé.</p>',
                'extra' => [
                    'quote_fr' => 'Suites, appartements et chambres délicatement décorés, restaurants, spa et piscine aux cascades.',
                    'quote_en' => 'Suites, apartments and rooms, restaurants, spa and cascading pool.',
                ],
            ],
            [
                'section_key' => 'amenities',
                'sort_order' => 25,
                'title_fr' => 'Équipements mis en avant',
                'title_en' => 'Featured amenities',
                'extra' => [
                    'items' => [
                        ['icon' => 'pool', 'label_fr' => 'Piscine 24h/24', 'label_en' => '24/7 pool'],
                        ['icon' => 'leaf', 'label_fr' => 'Spa', 'label_en' => 'Spa'],
                        ['icon' => 'wifi', 'label_fr' => 'Wi-Fi illimité', 'label_en' => 'Unlimited Wi-Fi'],
                        ['icon' => 'snow', 'label_fr' => 'Climatisation', 'label_en' => 'Air conditioning'],
                        ['icon' => 'tv', 'label_fr' => 'CANAL+', 'label_en' => 'CANAL+'],
                    ],
                ],
            ],
            [
                'section_key' => 'services',
                'sort_order' => 30,
                'title_fr' => 'Services',
                'title_en' => 'Services',
                'extra' => [
                    'items' => [
                        ['icon' => 'wifi', 'title_fr' => 'Internet haut débit', 'text_fr' => 'Wifi illimité partout, câble Ethernet pour les plus exigeants.', 'title_en' => 'High-speed internet', 'text_en' => 'Unlimited Wi-Fi everywhere, Ethernet for power users.'],
                        ['icon' => 'leaf', 'title_fr' => 'Spa & Bien-être', 'text_fr' => 'Soins 7j/7. Piscine et cascades accessibles 24h/24.', 'title_en' => 'Spa & wellness', 'text_en' => 'Treatments 7 days a week. Pool open 24/7.'],
                        ['icon' => 'food', 'title_fr' => 'Restauration', 'text_fr' => 'Restaurants, bars, snack et coffee club sur place.', 'title_en' => 'Dining', 'text_en' => 'Restaurants, bars, snack and coffee club on site.'],
                        ['icon' => 'car', 'title_fr' => 'Transport', 'text_fr' => 'Navette aéroport et véhicules avec chauffeur sur demande.', 'title_en' => 'Transport', 'text_en' => 'Airport shuttle and chauffeur cars on request.'],
                    ],
                ],
            ],
            [
                'section_key' => 'rooms',
                'sort_order' => 40,
                'title_fr' => 'Hébergements',
                'title_en' => 'Accommodations',
                'subtitle_fr' => 'Du studio cosy à la suite executive, chaque espace allie confort hôtelier et autonomie résidentielle.',
                'subtitle_en' => 'From cosy studios to executive suites, every space blends hotel comfort with residential freedom.',
                'extra' => [],
            ],
            [
                'section_key' => 'restaurants',
                'sort_order' => 50,
                'title_fr' => 'Restaurants',
                'title_en' => 'Restaurants',
                'subtitle_fr' => 'Cuisine camerounaise et internationale, grillades au bord de la piscine, coffee club et cave à vins.',
                'subtitle_en' => 'Cameroonian and international cuisine, poolside grills, coffee club and wine cellar.',
                'extra' => [],
            ],
            [
                'section_key' => 'spa',
                'sort_order' => 60,
                'title_fr' => 'Spa',
                'title_en' => 'Spa',
                'subtitle_fr' => 'Rituels, soins signature, piscine et cascades des Jardins de Lym.',
                'subtitle_en' => 'Rituals, signature treatments, pool and cascades at Jardins de Lym.',
                'extra' => [],
            ],
            [
                'section_key' => 'cta',
                'sort_order' => 70,
                'title_fr' => 'Réservez votre séjour à Bastos',
                'title_en' => 'Book your stay in Bastos',
                'subtitle_fr' => 'Suites, appartements et chambres au cœur du quartier diplomatique — confirmation personnalisée sous 24 h.',
                'subtitle_en' => 'Suites, apartments and rooms in the diplomatic district — personal confirmation within 24 hours.',
                'extra' => [
                    'points_fr' => ["Réponse sous 24 h", 'Équipe sur place', 'Sans frais cachés'],
                    'points_en' => ['Reply within 24 h', 'On-site team', 'No hidden fees'],
                ],
            ],
        ];
    }
}
