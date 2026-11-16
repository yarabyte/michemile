<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function validate(?string $token): bool
    {
        $session = $_SESSION[self::KEY] ?? '';
        return is_string($token) && $session !== '' && hash_equals($session, $token);
    }

    public static function requireValid(?string $token): void
    {
        if (!self::validate($token)) {
            http_response_code(419);
            exit('Jeton CSRF invalide.');
        }
    }
}
