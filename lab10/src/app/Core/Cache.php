<?php
declare(strict_types=1);
namespace App\Core;

final class Cache
{
    private static ?\Redis $connection = null;
    private static bool $failed = false;
    private static string $status = 'BYPASS';

    public static function redis(): ?\Redis
    {
        if (self::$failed || !config('cache')['enabled'] || !class_exists(\Redis::class)) return null;
        if (self::$connection) return self::$connection;
        try {
            $c = config('cache');
            $redis = new \Redis();
            $redis->connect($c['host'], $c['port'], $c['timeout']);
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $c['timeout']);
            $redis->ping();
            return self::$connection = $redis;
        } catch (\RedisException $e) {
            self::failure($e);
            return null;
        }
    }

    private static function failure(\RedisException $e): void
    {
        // После первой ошибки до конца HTTP-запроса Redis больше не вызывается.
        self::$failed = true;
        self::$connection = null;
        self::status('BYPASS');
        error_log('Redis unavailable; cache bypassed');
    }

    public static function key(string $key): string { return config('cache')['prefix'] . $key; }
    public static function status(?string $value = null): string
    {
        if ($value !== null) {
            self::$status = $value;
            if (PHP_SAPI !== 'cli' && !headers_sent()) header('X-Cache: ' . $value);
        }
        return self::$status;
    }

    public static function get(string $key, bool &$found = false): mixed
    {
        $found = false;
        $redis = self::redis();
        if (!$redis) return null;
        try {
            $raw = $redis->get(self::key($key));
            if ($raw === false) return null;
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !array_key_exists('value', $data)) return null;
            $found = true;
            return $data['value'];
        } catch (\JsonException $e) {
            // Повреждённый JSON считается промахом; результат БД перезапишет ключ.
            return null;
        } catch (\RedisException $e) { self::failure($e); return null; }
    }

    public static function set(string $key, mixed $value, int $ttl): bool
    {
        $redis = self::redis();
        if (!$redis) return false;
        $ttl = max(1, $ttl);
        if (config('cache')['jitter']) $ttl += random_int(0, (int) floor($ttl * 0.15));
        try {
            return $redis->setex(self::key($key), $ttl, json_encode(['value' => $value], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } catch (\RedisException $e) { self::failure($e); return false; }
    }

    public static function remember(string $key, int $ttl, callable $loader): mixed
    {
        $off = config('debug') && strtolower($_SERVER['HTTP_X_CACHE_MODE'] ?? '') === 'off';
        if ($off || !self::redis()) {
            self::status('BYPASS');
            return $loader();
        }
        $found = false;
        $value = self::get($key, $found);
        if ($found) { self::status('HIT'); return $value; }
        $value = $loader();
        $saved = self::set($key, $value, $ttl);
        self::status($saved ? 'MISS' : 'BYPASS');
        return $value;
    }

    public static function version(string $scope): string
    {
        if (config('debug') && strtolower($_SERVER['HTTP_X_CACHE_MODE'] ?? '') === 'off') return 'bypass';
        $redis = self::redis();
        if (!$redis) return 'bypass';
        try {
            $key = self::key($scope . ':version');
            // Случайный начальный номер исключает совпадение со старыми ключами
            // при удалении только ключа версии; затем используется INCR.
            $redis->set($key, (string) random_int(1, 1000000000000), ['nx']);
            return (string) $redis->get($key);
        } catch (\RedisException $e) { self::failure($e); return 'bypass'; }
    }

    public static function invalidate(string $resource): void
    {
        self::status('BYPASS');
        $redis = self::redis();
        if (!$redis) return;
        $scopes = $resource === 'categories' ? ['categories', 'tickets'] : ['tickets'];
        try {
            foreach ($scopes as $scope) {
                self::version($scope);
                $redis->incr(self::key($scope . ':version'));
            }
        } catch (\RedisException $e) { self::failure($e); }
    }

    public static function clear(): void
    {
        $redis = self::redis();
        if (!$redis) return;
        try {
            // SCAN, затем DEL только нашего префикса. FLUSHDB не используется.
            $cursor = null;
            do {
                $keys = $redis->scan($cursor, self::key('*'), 100);
                if ($keys) $redis->del($keys);
            } while ($cursor !== 0);
        } catch (\RedisException $e) { self::failure($e); }
    }
}
