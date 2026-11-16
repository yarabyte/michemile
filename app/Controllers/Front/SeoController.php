<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Models\Accommodation;
use App\Models\Restaurant;
use App\Models\SpaPage;

final class SeoController
{
    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $urls = [
            ['loc' => url('/'), 'prio' => '1.0'],
            ['loc' => url('/nos-suites/'), 'prio' => '0.9'],
            ['loc' => url('/nos-appartements/'), 'prio' => '0.9'],
            ['loc' => url('/nos-chambres/'), 'prio' => '0.9'],
            ['loc' => url('/restaurants/'), 'prio' => '0.8'],
            ['loc' => url('/reservation/'), 'prio' => '0.9'],
            ['loc' => url('/contact/'), 'prio' => '0.7'],
            ['loc' => url('/spa/'), 'prio' => '0.8'],
        ];
        foreach (Accommodation::published() as $r) {
            $urls[] = ['loc' => url('/chambres/' . $r['slug'] . '/'), 'prio' => '0.8'];
        }
        foreach (Restaurant::published() as $r) {
            $urls[] = ['loc' => url('/restaurants/' . $r['slug'] . '/'), 'prio' => '0.8'];
        }
        foreach (SpaPage::published() as $r) {
            $urls[] = ['loc' => url('/spa/' . $r['slug'] . '/'), 'prio' => '0.8'];
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            echo '<url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc><changefreq>weekly</changefreq><priority>' . $u['prio'] . '</priority></url>';
        }
        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        $admin = trim((string) \App\Core\Env::get('ADMIN_PATH', 'admin'), '/');
        echo "User-agent: *\n";
        echo "Disallow: /{$admin}/\n";
        echo "Sitemap: " . url('/sitemap.xml') . "\n";
    }
}
