<?php
declare(strict_types=1);

namespace App\Core;

final class Env
{
    private static array $values = [];
    private static bool $loaded = false;

    /** @var array<string, true> */
    private static array $fromProcess = [];

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        self::$fromProcess = self::snapshotProcessKeys();

        $fileValues = [];
        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }
                $fileValues[$key] = $value;
            }
        } elseif (
            !isset(self::$fromProcess['DB_HOST'])
            && !isset(self::$fromProcess['DATABASE_URL'])
            && !isset(self::$fromProcess['MYSQL_URL'])
        ) {
            throw new \RuntimeException('.env introuvable. Copiez .env.example vers .env hors webroot.');
        }

        // Variables process (Coolify/Docker) priment sur le fichier .env
        foreach ($fileValues as $key => $value) {
            if (isset(self::$fromProcess[$key])) {
                self::$values[$key] = (string) self::processEnv($key);
            } else {
                self::$values[$key] = $value;
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
            }
        }

        foreach (['DATABASE_URL', 'MYSQL_URL', 'DB_URL'] as $urlKey) {
            $url = null;
            if (isset(self::$fromProcess[$urlKey])) {
                $url = self::processEnv($urlKey);
            } elseif (isset($fileValues[$urlKey])) {
                $url = $fileValues[$urlKey];
            }
            if (is_string($url) && $url !== '') {
                self::$values[$urlKey] = $url;
                self::applyDatabaseUrl($url);
                break;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $fromProcess = self::processEnv($key);
        if ($fromProcess !== false && $fromProcess !== null && $fromProcess !== '') {
            return $fromProcess;
        }
        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null || $v === '') {
            return $default;
        }
        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, true> */
    private static function snapshotProcessKeys(): array
    {
        $keys = [];
        foreach ($_ENV as $key => $value) {
            if (is_string($key) && $value !== '' && $value !== null) {
                $keys[$key] = true;
            }
        }
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && is_string($value) && $value !== '' && !str_starts_with($key, 'HTTP_')) {
                $keys[$key] = true;
            }
        }
        return $keys;
    }

    private static function processEnv(string $key): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        $v = getenv($key);
        return $v === false ? null : $v;
    }

    /**
     * Coolify / Docker : mysql://user:pass@host:3306/dbname
     */
    private static function applyDatabaseUrl(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return;
        }

        $map = [
            'DB_HOST' => $parts['host'],
            'DB_PORT' => (string) ($parts['port'] ?? 3306),
            'DB_NAME' => ltrim((string) ($parts['path'] ?? '/default'), '/') ?: 'default',
            'DB_USER' => isset($parts['user']) ? rawurldecode($parts['user']) : 'mysql',
            'DB_PASS' => isset($parts['pass']) ? rawurldecode($parts['pass']) : '',
        ];

        foreach ($map as $key => $value) {
            // DB_* déjà fournies explicitement par Coolify → on les garde
            if (isset(self::$fromProcess[$key])) {
                self::$values[$key] = (string) self::processEnv($key);
                continue;
            }
            self::$values[$key] = $value;
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
