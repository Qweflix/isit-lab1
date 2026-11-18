<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\{Auth, Database};
final class AuthController
{
    public function login(): void
    {
        $data = json_input();
        if (!is_string($data['email'] ?? null) || !is_string($data['password'] ?? null)) {
            json_error('Укажите email и пароль', 422);
        }
        $s = Database::connection()->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email=:email');
        $s->execute(['email' => mb_strtolower(trim($data['email']))]);
        $user = $s->fetch();
        // Даже для неизвестного email выполняем проверку хеша.
        $valid = password_verify($data['password'], $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (!$user || !$valid) json_error('Неверный email или пароль', 401);
        $token = bin2hex(random_bytes(32));
        $expires = gmdate('c', time() + config('token_ttl'));
        $s = Database::connection()->prepare('INSERT INTO auth_tokens(user_id,token_hash,expires_at) VALUES (:user_id,:hash,:expires)');
        $s->execute(['user_id' => $user['id'], 'hash' => hash('sha256', $token), 'expires' => $expires]);
        unset($user['password_hash']);
        json_response(['token' => $token, 'expires_at' => $expires, 'user' => $user]);
    }
    public function me(): void { json_response(Auth::user()); }
    public function logout(): void
    {
        Auth::user();
        $s = Database::connection()->prepare('DELETE FROM auth_tokens WHERE token_hash=:hash');
        $s->execute(['hash' => hash('sha256', Auth::token())]);
        no_content();
    }
}
