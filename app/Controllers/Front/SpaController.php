<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\I18n;
use App\Core\View;
use App\Models\SpaPage;

final class SpaController
{
    public function index(): void
    {
        $pages = SpaPage::published();
        $ogImage = asset('img/og-default.jpg');
        foreach ($pages as $page) {
            if (!empty($page['image_url'])) {
                $ogImage = (string) $page['image_url'];
                break;
            }
        }
        View::render('public/spa', [
            'title' => __('nav.spa') . ' — Micheline & Emile',
            'metaDescription' => __('seo.spa_desc'),
            'pages' => $pages,
            'canonical' => url('/spa/'),
            'ogImage' => $ogImage,
            'ogImageAlt' => __('nav.spa'),
        ]);
    }

    public function show(string $slug): void
    {
        $page = SpaPage::findBySlug($slug);
        if (!$page) {
            http_response_code(404);
            View::render('public/404', ['title' => __('error.page_title')]);
            return;
        }
        $title = I18n::pick($page, 'title');
        $image = !empty($page['image_url']) ? (string) $page['image_url'] : asset('img/og-default.jpg');
        View::render('public/spa-detail', [
            'title' => $title . ' — Micheline & Emile',
            'metaDescription' => I18n::pick($page, 'lead') ?: I18n::pick($page, 'meta_desc') ?: __('seo.spa_desc'),
            'page' => $page,
            'canonical' => url('/spa/' . $page['slug'] . '/'),
            'ogImage' => $image,
            'ogImageAlt' => $title,
        ]);
    }

    public function legacy(string $path): void
    {
        $page = SpaPage::findByLegacyPath('/' . trim($path, '/') . '/');
        if (!$page) {
            http_response_code(404);
            View::render('public/404', ['title' => __('error.page_title')]);
            return;
        }
        // Keep legacy URL content, or 301 to /spa/{slug}/
        redirect('/spa/' . $page['slug'] . '/', 301);
    }
}
