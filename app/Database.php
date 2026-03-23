<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require CONFIG_PATH . '/database.php';

        $host = $config['host'];
        $port = $config['port'];
        $name = $config['name'];
        $user = $config['user'];
        $password = $config['password'];
        $dsn = $config['dsn'] ?: sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database connection failed. Set DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD before running the app.',
                previous: $exception
            );
        }

        return self::$connection;
    }
}
