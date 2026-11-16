<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\I18n;
use App\Core\View;
use App\Models\Accommodation;
use App\Models\HomeSection;
use App\Models\Restaurant;
use App\Models\SpaPage;
use App\Services\Settings;

final class HomeController
{
    public function index(): void
    {
        $locale = I18n::locale();
        $titleKey = 'meta_title_' . $locale;
        $descKey = 'meta_desc_' . $locale;
        $ogImage = Settings::get('hero_image', '') ?: asset('img/og-default.jpg');
        $title = (string) Settings::get($titleKey, '');
        if ($title === '') {
            $title = (string) Settings::get('meta_title_fr', '');
        }
        if ($title === '') {
            $title = __('seo.home_title');
        }
        $desc = (string) Settings::get($descKey, '');
        if ($desc === '') {
            $desc = (string) Settings::get('meta_desc_fr', '');
        }
        if ($desc === '') {
            $desc = __('seo.home_desc');
        }

        View::render('public/home', [
            'title' => $title,
            'metaDescription' => $desc,
            'sections' => HomeSection::allAdmin(),
            'rooms' => Accommodation::published(),
            'restaurants' => Restaurant::published(),
            'spaPages' => SpaPage::published(),
            'canonical' => url('/'),
            'ogType' => 'website',
            'ogImage' => $ogImage,
            'ogImageAlt' => __('seo.og_alt'),
            'schema' => $this->hotelSchema($ogImage),
        ]);
    }

    private function hotelSchema(string $image): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            'name' => 'Les Résidences hôtelières Micheline & Emile',
            'url' => url('/'),
            'image' => absolute_url($image),
            'email' => 'booking@michemil.com',
            'telephone' => Settings::get('phone', '+237 653 29 02 58'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Yaoundé',
                'addressRegion' => 'Bastos',
                'addressCountry' => 'CM',
            ],
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Wi-Fi'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Pool'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Spa'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Air conditioning'],
            ],
        ];
    }
}
