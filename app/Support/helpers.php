<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Env;
use App\Core\I18n;
use App\Services\HtmlSanitizer;

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function purify(?string $html): string
{
    return HtmlSanitizer::clean((string) $html);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function base_url(): string
{
    // En HTTP, préférer l’hôte de la requête (évite localhost vs 127.0.0.1 qui casse la CSP).
    if (PHP_SAPI !== 'cli') {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
                || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
            return ($https ? 'https' : 'http') . '://' . $host;
        }
    }
    return rtrim((string) Env::get('APP_URL', ''), '/');
}

function url(string $path = '/'): string
{
    $base = base_url();
    $path = '/' . ltrim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/') . '/';
    }
    return $base . ($path === '/' ? '/' : $path);
}

function asset(string $path): string
{
    // Chemin relatif : même origine que la page → CSS/JS non bloqués par la CSP.
    return '/assets/' . ltrim($path, '/');
}

/**
 * Absolute URL for crawlers (Open Graph, Twitter, JSON-LD).
 * Leaves http(s) URLs unchanged; prefixes base_url() for relative paths.
 */
function absolute_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return base_url() . '/';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
}

function default_og_image(): string
{
    return absolute_url(asset('img/og-default.jpg'));
}

function admin_url(string $path = ''): string
{
    $prefix = trim((string) Env::get('ADMIN_PATH', 'admin'), '/');
    $path = trim($path, '/');
    return url($prefix . ($path !== '' ? '/' . $path : ''));
}

function redirect(string $to, int $code = 302): never
{
    if (!preg_match('#^https?://#i', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $code);
    exit;
}

function old(string $key, string $default = ''): string
{
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $v;
}

function __(string $key, array $replace = []): string
{
    return I18n::get($key, $replace);
}

function locale(): string
{
    return I18n::locale();
}

function format_fcfa(int $amount): string
{
    return number_format($amount, 0, ',', "\u{00A0}") . "\u{00A0}FCFA";
}

function password_algo(): string|int
{
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

function component(string $name, array $data = []): void
{
    \App\Core\View::partial('components/' . ltrim($name, '/'), $data);
}

function admin_path(): string
{
    $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $prefix = '/' . trim((string) Env::get('ADMIN_PATH', 'admin'), '/');
    if (!str_starts_with($uri, $prefix)) {
        return '';
    }
    $rest = trim(substr($uri, strlen($prefix)), '/');
    return $rest;
}

function admin_is_active(string $path = ''): bool
{
    $current = admin_path();
    $path = trim($path, '/');
    if ($path === '') {
        return $current === '' || $current === 'login';
    }
    return $current === $path || str_starts_with($current, $path . '/');
}

function ui_classes(?string ...$parts): string
{
    return trim(implode(' ', array_filter($parts, static fn($p) => $p !== null && $p !== '')));
}

function slugify(string $text): string
{
    $text = trim(mb_strtolower($text));
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ä'=>'a','ã'=>'a','å'=>'a','æ'=>'ae',
        'ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o','õ'=>'o','ø'=>'o','œ'=>'oe',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y','ß'=>'ss',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'fiche';
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' o';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
    }
    return number_format($bytes / (1024 * 1024), 1, ',', ' ') . ' Mo';
}

function default_intro_image(): string
{
    return '/assets/img/content/WhatsApp-Image-2026-06-23-at-11.33.48-960x1080.jpeg';
}

/**
 * Find or register a local public file in the media library.
 */
function ensure_media_from_public_path(string $publicPath, ?string $altFr = null): ?int
{
    $publicPath = '/' . ltrim($publicPath, '/');
    $existing = \App\Core\Database::fetch('SELECT id FROM media WHERE public_path = ? LIMIT 1', [$publicPath]);
    if ($existing) {
        return (int) $existing['id'];
    }

    $disk = dirname(__DIR__, 2) . '/public' . $publicPath;
    if (!is_file($disk)) {
        return null;
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($disk) ?: 'application/octet-stream';
    $width = null;
    $height = null;
    if (str_starts_with($mime, 'image/')) {
        $size = @getimagesize($disk);
        if ($size) {
            $width = (int) $size[0];
            $height = (int) $size[1];
        }
    }

    return (int) \App\Core\Database::insert(
        'INSERT INTO media (disk_path, public_path, original_name, mime, width, height, size_bytes, alt_fr)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $disk,
            $publicPath,
            basename($publicPath),
            $mime,
            $width,
            $height,
            filesize($disk) ?: 0,
            $altFr,
        ]
    );
}

function home_section_label(string $key): string
{
    return match ($key) {
        'hero' => 'Bannière (haut de page)',
        'intro', 'residence' => 'La résidence',
        'amenities' => 'Bandeau équipements',
        'services' => 'Services',
        'rooms', 'hebergements' => 'Bloc hébergements',
        'restaurants' => 'Bloc restaurants',
        'spa' => 'Bloc spa',
        'contact', 'cta' => 'Appel à l’action',
        default => ucfirst(str_replace('_', ' ', $key)),
    };
}

function home_section_help(string $key): string
{
    return match ($key) {
        'hero' => 'Slider (max 3 photos) et texte au-dessus du formulaire de réservation.',
        'intro' => 'Texte « La résidence » avec citation et photo.',
        'amenities' => 'Petites pastilles sous la bannière (piscine, Wi‑Fi…).',
        'services' => 'Les 4 cartes services (internet, spa, resto, transport).',
        'rooms' => 'Titre et phrase au-dessus de la liste des chambres.',
        'restaurants' => 'Titre et phrase au-dessus des restaurants.',
        'spa' => 'Titre et phrase au-dessus du spa.',
        'cta' => 'Bandeau final « Réservez votre séjour ».',
        default => '',
    };
}

function activity_label(string $action, ?string $entityType = null, ?int $entityId = null): string
{
    $map = [
        'accommodation.create' => 'Hébergement créé',
        'accommodation.update' => 'Hébergement modifié',
        'accommodation.delete' => 'Hébergement supprimé',
        'accommodation.reorder' => 'Ordre des hébergements modifié',
        'restaurant.create' => 'Restaurant créé',
        'restaurant.update' => 'Restaurant modifié',
        'spa.create' => 'Page spa créée',
        'spa.update' => 'Page spa modifiée',
        'home.update' => 'Section d’accueil modifiée',
        'booking.update' => 'Réservation mise à jour',
        'message.update' => 'Message mis à jour',
        'settings.update' => 'Paramètres enregistrés',
        'user.save' => 'Utilisateur enregistré',
        'auth.2fa_enable' => 'Double authentification activée',
        'auth.2fa_disable' => 'Double authentification désactivée',
        'auth.login' => 'Connexion',
    ];
    $label = $map[$action] ?? str_replace(['.', '_'], [' — ', ' '], $action);
    if ($entityType && $entityId) {
        $types = [
            'accommodation' => 'hébergement',
            'restaurant' => 'restaurant',
            'spa_page' => 'spa',
            'home_section' => 'accueil',
            'booking' => 'réservation',
            'message' => 'message',
        ];
        $type = $types[$entityType] ?? $entityType;
        $label .= ' (' . $type . ' n°' . $entityId . ')';
    }
    return $label;
}

function booking_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Nouvelle',
        'confirmed' => 'Confirmée',
        'cancelled' => 'Annulée',
        'completed' => 'Terminée',
        default => $status,
    };
}

function message_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Nouveau',
        'read' => 'Lu',
        'archived' => 'Archivé',
        default => $status,
    };
}

function category_label(string $category): string
{
    return match ($category) {
        'suites' => 'Suites',
        'apparts' => 'Appartements',
        'chambres' => 'Chambres',
        default => $category,
    };
}

function ui_icon_paths(): array
{
    return [
        'check' => 'M5 12l5 5L20 7',
        'wifi' => 'M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 19.5h.01',
        'leaf' => 'M5 19c0-8 5-14 15-14 0 10-6 15-14 15M5 19l7-7',
        'food' => 'M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10M17 3c-2 1-3 4-3 7h3v11',
        'car' => 'M3 13l2-6h14l2 6v5H3zM3 13h18M7 18v2M17 18v2',
        'pool' => 'M2 19c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5M8 15V5a2 2 0 0 1 4 0M16 15V5a2 2 0 0 0-4 0M8 9h8',
        'snow' => 'M12 2v20M3.3 7l17.4 10M3.3 17L20.7 7',
        'tv' => 'M3 6h18v11H3zM8 21h8M12 17v4',
        'kitchen' => 'M4 3v7a2 2 0 0 0 2 2h1v9M8 3v7M12 21V10h7a2 2 0 0 0 2-2V3',
        'parking' => 'M5 21V3h8a5 5 0 0 1 0 10H9M9 13v8',
        'bath' => 'M4 12h16v2a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-2zm3-5V4M17 12V7a3 3 0 0 0-3-3',
        'bed' => 'M3 18v-5a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v5M3 18h18M7 10V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v3',
        'safe' => 'M4 5h16v14H4zM12 12h.01M8 9v6M16 9v6',
        'desk' => 'M4 10h16v8H4zM8 10V6h8v4M8 14h.01M16 14h.01',
        'sun' => 'M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8z',
    ];
}

function ui_icon(string $key, string $class = ''): string
{
    $paths = ui_icon_paths();
    $d = $paths[$key] ?? $paths['check'];
    $attrs = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<svg' . $attrs . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6">'
        . '<path d="' . e($d) . '" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
}

function equipment_icon_options(): array
{
    return [
        'check' => 'Standard',
        'wifi' => 'Wi‑Fi',
        'snow' => 'Climatisation',
        'tv' => 'Télévision',
        'kitchen' => 'Cuisine',
        'parking' => 'Parking',
        'pool' => 'Piscine',
        'bath' => 'Salle de bain',
        'bed' => 'Literie',
        'safe' => 'Coffre',
        'desk' => 'Bureau',
        'sun' => 'Balcon / soleil',
        'leaf' => 'Spa / nature',
        'food' => 'Restauration',
        'car' => 'Transport',
    ];
}
