<?php
declare(strict_types=1);

namespace App\Core;

final class I18n
{
    private static string $locale = 'fr';
    private static array $lines = [];

    public static function boot(): void
    {
        $supported = ['fr', 'en'];
        $locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? Env::get('APP_LOCALE', 'fr');
        if (!in_array($locale, $supported, true)) {
            $locale = 'fr';
        }
        if (isset($_GET['lang'])) {
            setcookie('lang', $locale, [
                'expires' => time() + 86400 * 365,
                'path' => '/',
                'secure' => Env::bool('SESSION_SECURE', true),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }
        self::$locale = $locale;
        $file = dirname(__DIR__, 2) . '/lang/' . $locale . '/messages.php';
        self::$lines = is_file($file) ? require $file : [];
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /** Run a callback with a temporary locale (e.g. guest emails). */
    public static function withLocale(string $locale, callable $callback): mixed
    {
        $supported = ['fr', 'en'];
        if (!in_array($locale, $supported, true)) {
            $locale = 'fr';
        }
        $previous = self::$locale;
        $previousLines = self::$lines;
        self::$locale = $locale;
        $file = dirname(__DIR__, 2) . '/lang/' . $locale . '/messages.php';
        self::$lines = is_file($file) ? require $file : [];
        try {
            return $callback();
        } finally {
            self::$locale = $previous;
            self::$lines = $previousLines;
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        $text = self::$lines[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }

    public static function pick(array $row, string $field): string
    {
        $locale = self::$locale;
        $key = $field . '_' . $locale;
        if (!empty($row[$key])) {
            return (string) $row[$key];
        }
        $fallback = $field . '_fr';
        return (string) ($row[$fallback] ?? '');
    }
}
