<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) Env::get('DB_HOST', '127.0.0.1');
        $socket = (string) Env::get('DB_SOCKET', '');
        // Sur macOS (DBngin/Herd), "localhost" tente un socket Unix souvent absent → préférer 127.0.0.1
        if ($host === 'localhost' && $socket === '') {
            $host = '127.0.0.1';
        }

        if ($socket !== '') {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                $socket,
                Env::get('DB_NAME')
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                Env::get('DB_PORT', '3306'),
                Env::get('DB_NAME')
            );
        }

        try {
            self::$pdo = new PDO($dsn, (string) Env::get('DB_USER'), (string) Env::get('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            throw new \PDOException(
                'Connexion MySQL impossible (' . $e->getMessage() . '). '
                . 'Vérifiez DB_HOST/DB_PORT (en local macOS: 127.0.0.1) ou DB_SOCKET=/tmp/mysql_3306.sock (DBngin).',
                (int) $e->getCode(),
                $e
            );
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::pdo()->lastInsertId();
    }
}
