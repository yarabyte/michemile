<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\ActivityLogger;
use App\Services\LoginThrottle;

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        return Database::fetch(
            'SELECT u.*, r.slug AS role_slug, r.permissions
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.is_active = 1 LIMIT 1',
            [(int) $id]
        );
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $throttle = new LoginThrottle();
        if ($throttle->isLocked(client_ip(), $email)) {
            return ['ok' => false, 'error' => 'Trop de tentatives. Réessayez plus tard.', 'locked' => true];
        }

        $user = Database::fetch(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        );

        $ok = $user && password_verify($password, $user['password_hash']);
        $throttle->record(client_ip(), $email, $ok);

        if (!$ok) {
            return ['ok' => false, 'error' => 'Identifiants incorrects.', 'locked' => false];
        }

        if (password_needs_rehash($user['password_hash'], password_algo())) {
            $newHash = password_hash($password, password_algo());
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [$newHash, $user['id']]);
        }

        if ((int) $user['totp_enabled'] === 1) {
            $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
            return ['ok' => true, 'needs_2fa' => true];
        }

        self::loginUser($user);
        return ['ok' => true, 'needs_2fa' => false];
    }

    public static function complete2fa(string $code): bool
    {
        $uid = $_SESSION['pending_2fa_user_id'] ?? null;
        if (!$uid) {
            return false;
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND is_active = 1', [(int) $uid]);
        if (!$user || !(int) $user['totp_enabled']) {
            return false;
        }
        $tfa = \App\Services\TotpService::make();
        if (!$tfa->verifyCode((string) $user['totp_secret'], $code)) {
            return false;
        }
        unset($_SESSION['pending_2fa_user_id']);
        self::loginUser($user);
        return true;
    }

    private static function loginUser(array $user): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        ActivityLogger::log((int) $user['id'], 'auth.login');
    }

    public static function logout(): void
    {
        $uid = $_SESSION['user_id'] ?? null;
        if ($uid) {
            ActivityLogger::log((int) $uid, 'auth.logout');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $p['path'],
                'secure' => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Strict',
            ]);
        }
        session_destroy();
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            redirect(admin_url('login'));
        }
        return $user;
    }

    public static function can(array $user, string $permission): bool
    {
        $perms = json_decode((string) $user['permissions'], true) ?: [];
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }
}
