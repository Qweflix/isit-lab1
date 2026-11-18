<?php
declare(strict_types=1);
namespace App\Core;
final class Auth
{
    public static function token(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer ([a-f0-9]{64})$/D', $header, $m)) {
            header('WWW-Authenticate: Bearer');
            json_error('Требуется авторизация', 401);
        }
        return $m[1];
    }
    public static function user(): array
    {
        $hash = hash('sha256', self::token());
        $s = Database::connection()->prepare('SELECT u.id, u.name, u.email, u.role FROM users u JOIN auth_tokens t ON t.user_id=u.id WHERE t.token_hash=:hash AND t.expires_at>CURRENT_TIMESTAMP');
        $s->execute(['hash' => $hash]);
        $user = $s->fetch();
        if (!$user) {
            header('WWW-Authenticate: Bearer');
            json_error('Сессия истекла. Войдите снова.', 401);
        }
        return $user;
    }
    public static function requireRole(string $role): void
    {
        if (self::user()['role'] !== $role) json_error('Недостаточно прав для этого действия', 403);
    }
}
