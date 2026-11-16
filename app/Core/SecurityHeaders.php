<?php
declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function send(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; script-src 'self' 'unsafe-eval' https://www.google.com https://www.gstatic.com; worker-src 'self' blob:; frame-src 'self' https://www.google.com; connect-src 'self' https://www.google.com; base-uri 'self'; form-action 'self'");

        if (Env::bool('HSTS_ENABLED', true)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
