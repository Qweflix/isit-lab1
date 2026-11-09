<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
final class Database
{
    public static function connection(): PDO
    {
        static $pdo = null;
        if ($pdo === null) {
            $c = config('db');
            $dsn = "pgsql:host={$c['host']};port={$c['port']};dbname={$c['name']};connect_timeout=3";
            try {
            $pdo = new PDO($dsn, $c['user'], $c['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            } catch (\PDOException $e) {
                throw new DatabaseUnavailable("Database connection failed", 0, $e);
            }
        }
        return $pdo;
    }
}
