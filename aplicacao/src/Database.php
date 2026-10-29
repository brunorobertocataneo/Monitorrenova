<?php
declare(strict_types=1);
final class Database {
    public static function connection(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $host = getenv('DB_HOST') ?: 'database';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'monitor_renova';
            $user = getenv('DB_USER') ?: 'monitor';
            $password = getenv('DB_PASSWORD');
            if ($password === false || $password === '') throw new RuntimeException('Credencial de banco não configurada');
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        }
        return $pdo;
    }
}
