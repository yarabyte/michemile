<?php
declare(strict_types=1);

namespace App\Core;

final class App
{
    public static function bootstrap(): Router
    {
        $root = dirname(__DIR__, 2);
        Env::load($root . '/.env');

        $debug = Env::bool('APP_DEBUG', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $root . '/storage/logs/php-error.log');

        date_default_timezone_set('Africa/Douala');

        SecurityHeaders::send();
        Session::start();
        I18n::boot();

        return new Router();
    }
}
