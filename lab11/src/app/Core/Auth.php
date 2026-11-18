<?php
declare(strict_types=1);
namespace App\Core;

final class Auth
{
    public static function user(): array
    {
        $email = $_SERVER['PHP_AUTH_USER'] ?? '';
        $password = $_SERVER['PHP_AUTH_PW'] ?? '';
        $query = Database::connection()->prepare('SELECT id, name, email, role, password_hash FROM staff WHERE email = ?');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            header('WWW-Authenticate: Basic realm="Support Lab11"');
            \json_error('Требуется авторизация', 401);
        }
        unset($user['password_hash']);
        $user['id'] = (int) $user['id'];
        return $user;
    }

    public static function isSupervisor(int $id): bool
    {
        $query = Database::connection()->prepare('SELECT 1 FROM categories WHERE owner_id = ? LIMIT 1');
        $query->execute([$id]);
        return (bool) $query->fetchColumn();
    }
}
