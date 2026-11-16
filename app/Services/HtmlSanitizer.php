<?php
declare(strict_types=1);

namespace App\Services;

final class HtmlSanitizer
{
    private static ?\HTMLPurifier $purifier = null;

    public static function clean(string $html): string
    {
        if ($html === '') {
            return '';
        }
        return self::purifier()->purify($html);
    }

    private static function purifier(): \HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,ul,ol,li,a[href|title|rel|target],h2,h3,h4,blockquote,img[src|alt|width|height|loading]');
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $cache = dirname(__DIR__, 2) . '/storage/cache/htmlpurifier';
        if (!is_dir($cache)) {
            @mkdir($cache, 0750, true);
        }
        $config->set('Cache.SerializerPath', $cache);
        self::$purifier = new \HTMLPurifier($config);
        return self::$purifier;
    }
}
