<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\I18n;
use App\Core\View;

final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        try {
            $recipients = self::parseAddresses($to);
            if ($recipients === []) {
                throw new \RuntimeException('Aucun destinataire valide.');
            }
            foreach ($recipients as $email) {
                self::sendWithDetails($email, $subject, $htmlBody, $textBody);
            }
            return true;
        } catch (\Throwable $e) {
            error_log('Mailer send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array{ok:bool,driver:string,message:string,to:string}
     */
    public static function sendWithDetails(string $to, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        $to = strtolower(trim($to));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Adresse destinataire invalide: ' . $to);
        }

        $from = self::config('mail_from', 'MAIL_FROM', 'noreply@michemil.com');
        $fromName = self::config('mail_from_name', 'MAIL_FROM_NAME', 'Micheline & Emile');
        $driver = strtolower(self::config('mail_driver', 'MAIL_DRIVER', 'mail'));
        $replyList = self::bookingAddresses();
        $replyTo = $replyList[0] ?? $from;

        if ($driver === 'smtp') {
            self::sendSmtp($to, $subject, $htmlBody, $textBody, $from, $fromName, $replyTo);
            return [
                'ok' => true,
                'driver' => 'smtp',
                'to' => $to,
                'message' => 'E-mail SMTP envoyé avec succès à ' . $to . '.',
            ];
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . self::encodeAddress($fromName, $from),
            'Reply-To: ' . $replyTo,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            throw new \RuntimeException('PHP mail() a échoué. Passez en mode SMTP.');
        }
        return [
            'ok' => true,
            'driver' => 'mail',
            'to' => $to,
            'message' => 'E-mail envoyé via PHP mail() à ' . $to . '.',
        ];
    }

    /** First booking / contact inbox address (settings, then .env). */
    public static function bookingAddress(): string
    {
        $list = self::bookingAddresses();
        return $list[0] ?? 'booking@michemil.com';
    }

    /**
     * All booking notification recipients (comma/semicolon separated in settings).
     * @return list<string>
     */
    public static function bookingAddresses(): array
    {
        return self::parseAddresses(self::config('mail_booking', 'MAIL_BOOKING', 'booking@michemil.com'));
    }

    /**
     * @return array{admin:bool,guest:bool,admin_to:list<string>,guest_to:?string,errors:list<string>}
     */
    public static function sendBookingNotifications(array $booking): array
    {
        $result = [
            'admin' => false,
            'guest' => false,
            'admin_to' => [],
            'guest_to' => null,
            'errors' => [],
        ];

        $adminRecipients = self::bookingAddresses();
        $adminSubject = '[Réservation ' . ($booking['reference'] ?? '') . '] ' . ($booking['guest_name'] ?? '');
        try {
            ob_start();
            View::partial('emails/booking-admin', ['booking' => $booking]);
            $adminHtml = (string) ob_get_clean();
            if ($adminRecipients === []) {
                throw new \RuntimeException('Aucune boîte réservations configurée (mail_booking).');
            }
            foreach ($adminRecipients as $email) {
                self::sendWithDetails($email, $adminSubject, $adminHtml);
                $result['admin_to'][] = $email;
            }
            $result['admin'] = true;
        } catch (\Throwable $e) {
            $msg = 'Booking admin mail failed [' . ($booking['reference'] ?? '?') . ']: ' . $e->getMessage();
            error_log($msg);
            $result['errors'][] = $e->getMessage();
        }

        $guestEmail = strtolower(trim((string) ($booking['guest_email'] ?? '')));
        if ($guestEmail !== '' && filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            $result['guest_to'] = $guestEmail;
            try {
                $guestLocale = (string) ($booking['locale'] ?? 'fr');
                [$guestHtml, $guestSubject] = I18n::withLocale($guestLocale, static function () use ($booking): array {
                    ob_start();
                    View::partial('emails/booking-guest', ['booking' => $booking]);
                    $body = (string) ob_get_clean();
                    return [$body, __('booking.mail_subject', ['ref' => (string) ($booking['reference'] ?? '')])];
                });
                self::sendWithDetails($guestEmail, $guestSubject, $guestHtml);
                $result['guest'] = true;
            } catch (\Throwable $e) {
                $msg = 'Booking guest mail failed [' . ($booking['reference'] ?? '?') . ']: ' . $e->getMessage();
                error_log($msg);
                $result['errors'][] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Resolve a mail setting: DB settings first, then .env, then default.
     */
    public static function config(string $settingKey, string $envKey, mixed $default = ''): string
    {
        $fromDb = trim((string) Settings::get($settingKey, ''));
        if ($fromDb !== '') {
            return $fromDb;
        }
        $fromEnv = trim((string) Env::get($envKey, ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        return (string) $default;
    }

    /** @return array{driver:string,from:string,from_name:string,booking:string,host:string,port:string,user:string,secure:string,pass_set:bool} */
    public static function snapshot(): array
    {
        return [
            'driver' => self::config('mail_driver', 'MAIL_DRIVER', 'mail'),
            'from' => self::config('mail_from', 'MAIL_FROM', 'noreply@michemil.com'),
            'from_name' => self::config('mail_from_name', 'MAIL_FROM_NAME', 'Micheline & Emile'),
            'booking' => implode(', ', self::bookingAddresses()),
            'host' => self::config('smtp_host', 'SMTP_HOST'),
            'port' => self::config('smtp_port', 'SMTP_PORT', '587'),
            'user' => self::config('smtp_user', 'SMTP_USER'),
            'secure' => self::config('smtp_secure', 'SMTP_SECURE', 'tls'),
            'pass_set' => self::config('smtp_pass', 'SMTP_PASS') !== '',
        ];
    }

    /** @return list<string> */
    public static function parseAddresses(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $email = strtolower(trim($part));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $out, true)) {
                $out[] = $email;
            }
        }
        return $out;
    }

    private static function encodeAddress(string $name, string $email): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
    }

    private static function sendSmtp(
        string $to,
        string $subject,
        string $html,
        ?string $text,
        string $from,
        string $fromName,
        string $replyTo
    ): void {
        $host = self::config('smtp_host', 'SMTP_HOST');
        $port = (int) self::config('smtp_port', 'SMTP_PORT', '587');
        $user = self::config('smtp_user', 'SMTP_USER');
        $pass = self::config('smtp_pass', 'SMTP_PASS');
        $secure = strtolower(self::config('smtp_secure', 'SMTP_SECURE', 'tls'));

        if ($host === '') {
            throw new \RuntimeException('Hôte SMTP vide.');
        }
        if (in_array($port, [995, 993, 110, 143], true)) {
            throw new \RuntimeException(
                "Le port {$port} n’est pas un port SMTP. Utilisez 465 (SSL) ou 587 (STARTTLS)."
            );
        }

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;
        $fp = @fsockopen($remote, $port, $errno, $errstr, 20);
        if (!$fp) {
            throw new \RuntimeException("Connexion SMTP impossible : {$errstr} ({$errno}) — {$remote}:{$port}");
        }
        stream_set_timeout($fp, 20);
        try {
            self::smtpExpect($fp, 220);
            self::smtpCmd($fp, 'EHLO michemil.com', 250);
            if ($secure === 'tls') {
                self::smtpCmd($fp, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Échec STARTTLS.');
                }
                self::smtpCmd($fp, 'EHLO michemil.com', 250);
            }
            if ($user !== '') {
                self::smtpCmd($fp, 'AUTH LOGIN', 334);
                self::smtpCmd($fp, base64_encode($user), 334);
                self::smtpCmd($fp, base64_encode($pass), 235);
            }
            self::smtpCmd($fp, 'MAIL FROM:<' . $from . '>', 250);
            self::smtpCmd($fp, 'RCPT TO:<' . $to . '>', 250);
            self::smtpCmd($fp, 'DATA', 354);
            $boundary = 'b_' . bin2hex(random_bytes(8));
            $messageId = sprintf('<%s.%s@michemil.com>', bin2hex(random_bytes(8)), time());
            $msg = 'Date: ' . date('r') . "\r\n";
            $msg .= 'From: ' . self::encodeAddress($fromName, $from) . "\r\n";
            $msg .= 'To: <' . $to . ">\r\n";
            $msg .= 'Reply-To: ' . $replyTo . "\r\n";
            $msg .= 'Message-ID: ' . $messageId . "\r\n";
            $msg .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n\r\n";
            $plain = $text ?: trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $msg .= '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . $plain . "\r\n";
            $msg .= '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n";
            $msg .= '--' . $boundary . "--\r\n.\r\n";
            fwrite($fp, $msg);
            self::smtpExpect($fp, 250);
            self::smtpCmd($fp, 'QUIT', 221);
        } finally {
            fclose($fp);
        }
    }

    private static function smtpCmd($fp, string $cmd, int $expect): void
    {
        fwrite($fp, $cmd . "\r\n");
        self::smtpExpect($fp, $expect);
    }

    private static function smtpExpect($fp, int $code): void
    {
        $resp = '';
        while ($line = fgets($fp, 515)) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($resp === '') {
            throw new \RuntimeException('SMTP sans réponse (timeout).');
        }
        if (!str_starts_with($resp, (string) $code)) {
            throw new \RuntimeException('SMTP inattendu : ' . trim($resp));
        }
    }
}
