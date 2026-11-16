#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Télécharge les images WordPress externes et réécrit les chemins en local.
 * Usage: php bin/localize-images.php
 */

$root = dirname(__DIR__);
require $root . '/app/Support/helpers.php';
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
\App\Core\Env::load($root . '/.env');

use App\Core\Database;
use App\Services\Settings;

$jsonFile = $root . '/resources/content/mockup-content.json';
$importFile = $root . '/bin/import-content.php';
$destDir = $root . '/public/assets/img/content';
$publicPrefix = '/assets/img/content';

$extraUrls = [
    'https://michemil.com/wp-content/uploads/2026/07/JDL1.jpeg',
    'https://michemil.com/wp-content/uploads/2026/07/WhatsApp-Image-2026-06-23-at-11.33.48-960x1080.jpeg',
    'https://michemil.com/wp-content/uploads/2026/07/WhatsApp-Image-2026-06-23-at-11.33.48-600x450.jpeg',
    'https://michemil.com/wp-content/uploads/2026/07/WhatsApp-Image-2026-06-30-at-00.00.54.jpeg',
];

if (!is_file($jsonFile)) {
    fwrite(STDERR, "Fichier manquant: resources/content/mockup-content.json\n");
    exit(1);
}

$jsonRaw = (string) file_get_contents($jsonFile);
$data = json_decode($jsonRaw, true, 512, JSON_THROW_ON_ERROR);

/** @var list<string> $urls */
$urls = [];
$collect = static function (mixed $value) use (&$urls, &$collect): void {
    if (is_string($value) && str_starts_with($value, 'https://michemil.com/wp-content/uploads/')) {
        $urls[] = $value;
        return;
    }
    if (is_array($value)) {
        foreach ($value as $item) {
            $collect($item);
        }
    }
};
$collect($data);
foreach ($extraUrls as $url) {
    $urls[] = $url;
}
$urls = array_values(array_unique($urls));

if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
    fwrite(STDERR, "Impossible de créer {$destDir}\n");
    exit(1);
}

/** @var array<string, string> $map */
$map = [];
$downloaded = 0;
$skipped = 0;
$failed = 0;

foreach ($urls as $url) {
    $basename = rawurldecode(basename(parse_url($url, PHP_URL_PATH) ?: ''));
    if ($basename === '' || $basename === '/' || $basename === '.') {
        fwrite(STDERR, "Nom de fichier invalide pour {$url}\n");
        $failed++;
        continue;
    }

    $localPath = $destDir . '/' . $basename;
    $publicPath = $publicPrefix . '/' . $basename;
    $map[$url] = $publicPath;

    if (is_file($localPath) && filesize($localPath) > 0) {
        echo "OK (présent)  {$basename}\n";
        $skipped++;
        continue;
    }

    echo "Téléchargement {$url}\n";
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 60,
            'header' => "User-Agent: MicheMil-Localize/1.0\r\nAccept: image/*\r\n",
            'follow_location' => 1,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $bytes = @file_get_contents($url, false, $ctx);
    if ($bytes === false || $bytes === '') {
        fwrite(STDERR, "ÉCHEC téléchargement: {$url}\n");
        $failed++;
        unset($map[$url]);
        continue;
    }
    if (file_put_contents($localPath, $bytes) === false) {
        fwrite(STDERR, "ÉCHEC écriture: {$localPath}\n");
        $failed++;
        unset($map[$url]);
        continue;
    }
    echo "OK (téléchargé) {$basename} (" . strlen($bytes) . " octets)\n";
    $downloaded++;
}

if ($map === []) {
    fwrite(STDERR, "Aucune image localisée.\n");
    exit(1);
}

$replace = static function (string $text) use ($map): string {
    return strtr($text, $map);
};

$newJson = $replace($jsonRaw);
if ($newJson !== $jsonRaw) {
    file_put_contents(
        $jsonFile,
        json_encode(json_decode($newJson, true, 512, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
    echo "Mis à jour: resources/content/mockup-content.json\n";
}

if (is_file($importFile)) {
    $importRaw = (string) file_get_contents($importFile);
    $importNew = $replace($importRaw);
    if ($importNew !== $importRaw) {
        file_put_contents($importFile, $importNew);
        echo "Mis à jour: bin/import-content.php\n";
    }
}

$viewFiles = [
    $root . '/app/Views/public/home.php',
    $root . '/app/Views/public/restaurants.php',
];
foreach ($viewFiles as $viewFile) {
    if (!is_file($viewFile)) {
        continue;
    }
    $raw = (string) file_get_contents($viewFile);
    $new = $replace($raw);
    if ($new !== $raw) {
        file_put_contents($viewFile, $new);
        echo 'Mis à jour: ' . substr($viewFile, strlen($root) + 1) . "\n";
    }
}

$dbUpdated = 0;
try {
    $hero = (string) Settings::get('hero_image', '');
    if (isset($map[$hero])) {
        Settings::set('hero_image', $map[$hero]);
        $dbUpdated++;
        echo "DB settings.hero_image → {$map[$hero]}\n";
    }

    $rooms = Database::fetchAll('SELECT id, gallery_json FROM accommodations');
    foreach ($rooms as $room) {
        $galleryRaw = (string) ($room['gallery_json'] ?? '');
        $galleryNew = $replace($galleryRaw);
        if ($galleryNew !== $galleryRaw) {
            Database::query('UPDATE accommodations SET gallery_json = ? WHERE id = ?', [$galleryNew, (int) $room['id']]);
            $dbUpdated++;
        }
    }

    foreach (['restaurants', 'spa_pages'] as $table) {
        $rows = Database::fetchAll("SELECT id, image_url FROM {$table}");
        foreach ($rows as $row) {
            $url = (string) ($row['image_url'] ?? '');
            if (isset($map[$url])) {
                Database::query("UPDATE {$table} SET image_url = ? WHERE id = ?", [$map[$url], (int) $row['id']]);
                $dbUpdated++;
            }
        }
    }

    $sections = Database::fetchAll('SELECT id, extra_json FROM home_sections WHERE extra_json IS NOT NULL AND extra_json != ""');
    foreach ($sections as $section) {
        $extraRaw = (string) ($section['extra_json'] ?? '');
        $extraNew = $replace($extraRaw);
        if ($extraNew !== $extraRaw) {
            Database::query('UPDATE home_sections SET extra_json = ? WHERE id = ?', [$extraNew, (int) $section['id']]);
            $dbUpdated++;
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'DB non mise à jour: ' . $e->getMessage() . "\n");
}

echo "\nRésumé: téléchargés={$downloaded}, déjà présents={$skipped}, échecs={$failed}, maj DB={$dbUpdated}, mapping=" . count($map) . "\n";
if ($failed > 0) {
    exit(1);
}
