<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Env::bool('SESSION_SECURE', true);
        $name = (string) Env::get('SESSION_NAME', 'MICHEMILSESSID');
        $lifetime = (int) Env::get('SESSION_LIFETIME', 7200);

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        $savePath = dirname(__DIR__, 2) . '/storage/sessions';
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
        }

        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
