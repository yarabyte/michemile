<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

final class FormGuard
{
    /** Obscure honeypot field names (avoid browser autofill on "website"). */
    public const HONEYPOT_FIELDS = ['fax_number_hp', 'url_confirm_hp'];

    public static function assertHuman(array $post): void
    {
        foreach (self::HONEYPOT_FIELDS as $field) {
            if (!empty($post[$field])) {
                throw new \RuntimeException(__('form.rejected'));
            }
        }
        // Legacy names still rejected if a bot posts them
        if (!empty($post['website']) || !empty($post['company_url'])) {
            throw new \RuntimeException(__('form.rejected'));
        }

        $secret = (string) Env::get('RECAPTCHA_SECRET_KEY', '');
        if ($secret === '') {
            return;
        }
        $token = (string) ($post['g-recaptcha-response'] ?? '');
        if ($token === '') {
            throw new \RuntimeException(__('form.spam_missing'));
        }
        $payload = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => client_ip(),
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
        $json = $raw ? json_decode($raw, true) : null;
        if (!$json || empty($json['success'])) {
            throw new \RuntimeException(__('form.spam_failed'));
        }
    }
}
