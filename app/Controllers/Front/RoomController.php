<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\I18n;
use App\Core\View;
use App\Models\Accommodation;

final class RoomController
{
    private const CAT_PATHS = [
        'suites' => 'nos-suites',
        'apparts' => 'nos-appartements',
        'chambres' => 'nos-chambres',
    ];

    public function listing(string $categoryPath): void
    {
        $map = array_flip(self::CAT_PATHS);
        if (!isset($map[$categoryPath])) {
            http_response_code(404);
            View::render('public/404', ['title' => __('error.page_title')]);
            return;
        }
        $category = $map[$categoryPath];
        $rooms = Accommodation::published($category);
        $titles = [
            'suites' => __('room.heading_suites'),
            'apparts' => __('room.heading_appartments'),
            'chambres' => __('room.heading_rooms'),
        ];
        $descKeys = [
            'suites' => 'seo.suites_desc',
            'apparts' => 'seo.apparts_desc',
            'chambres' => 'seo.chambres_desc',
        ];
        View::render('public/rooms-list', [
            'title' => $titles[$category] . ' — Micheline & Emile',
            'heading' => $titles[$category],
            'metaDescription' => __($descKeys[$category]),
            'category' => $category,
            'rooms' => $rooms,
            'canonical' => url('/' . $categoryPath . '/'),
            'ogImage' => asset('img/og-default.jpg'),
        ]);
    }

    public function show(string $slug): void
    {
        $room = Accommodation::findBySlug($slug);
        if (!$room) {
            http_response_code(404);
            View::render('public/404', ['title' => __('error.room_title')]);
            return;
        }
        $name = I18n::pick($room, 'name');
        $cover = $this->cover($room) ?: asset('img/og-default.jpg');
        $description = I18n::pick($room, 'meta_desc') ?: I18n::pick($room, 'short');
        $similar = array_values(array_filter(
            Accommodation::published($room['category']),
            static fn ($r) => (int) $r['id'] !== (int) $room['id']
        ));
        View::render('public/room', [
            'title' => (I18n::pick($room, 'meta_title') ?: $name) . ' — Micheline & Emile',
            'metaDescription' => $description,
            'room' => $room,
            'similar' => $similar,
            'canonical' => url('/chambres/' . $room['slug'] . '/'),
            'ogImage' => $cover,
            'ogImageAlt' => $name,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'HotelRoom',
                'name' => $name,
                'description' => I18n::pick($room, 'short'),
                'image' => absolute_url($cover),
                'url' => url('/chambres/' . $room['slug'] . '/'),
                'occupancy' => ['@type' => 'QuantitativeValue', 'maxValue' => (int) $room['capacity']],
                'floorSize' => ['@type' => 'QuantitativeValue', 'value' => (int) $room['area_m2'], 'unitCode' => 'MTK'],
            ],
        ]);
    }

    private function cover(array $room): ?string
    {
        $gallery = json_decode((string) ($room['gallery_json'] ?? '[]'), true) ?: [];
        if (!empty($gallery[0])) {
            return (string) $gallery[0];
        }
        return null;
    }
}
