<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\I18n;
use App\Core\View;
use App\Models\Restaurant;

final class RestaurantController
{
    public function index(): void
    {
        View::render('public/restaurants', [
            'title' => __('nav.restaurants') . ' — Micheline & Emile',
            'metaDescription' => __('seo.restaurants_desc'),
            'restaurants' => Restaurant::published(),
            'canonical' => url('/restaurants/'),
            'ogImage' => asset('img/og-default.jpg'),
        ]);
    }

    public function show(string $slug): void
    {
        $resto = Restaurant::findBySlug($slug);
        if (!$resto) {
            http_response_code(404);
            View::render('public/404', ['title' => __('error.restaurant_title')]);
            return;
        }
        // Canonicalize legacy typos to current slug with 301 when needed
        if ($resto['slug'] !== $slug) {
            redirect('/restaurants/' . $resto['slug'] . '/', 301);
        }
        $name = I18n::pick($resto, 'name');
        $description = I18n::pick($resto, 'meta_desc') ?: I18n::pick($resto, 'tagline');
        $image = !empty($resto['image_url']) ? (string) $resto['image_url'] : asset('img/og-default.jpg');
        View::render('public/restaurant', [
            'title' => (I18n::pick($resto, 'meta_title') ?: $name) . ' — Micheline & Emile',
            'metaDescription' => $description,
            'restaurant' => $resto,
            'canonical' => url('/restaurants/' . $resto['slug'] . '/'),
            'ogImage' => $image,
            'ogImageAlt' => $name,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Restaurant',
                'name' => $name,
                'description' => $description,
                'url' => url('/restaurants/' . $resto['slug'] . '/'),
                'image' => absolute_url($image),
            ],
        ]);
    }
}
