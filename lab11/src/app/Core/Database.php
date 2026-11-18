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
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $name = getenv('DB_NAME') ?: 'supportdb';
            $user = getenv('DB_USER') ?: 'support';
            $password = getenv('DB_PASSWORD') ?: 'support-local';
            $pdo = new PDO("pgsql:host=$host;dbname=$name", $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return $pdo;
    }
}
