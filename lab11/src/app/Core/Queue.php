<?php
declare(strict_types=1);
namespace App\Core;

use Redis;
use RuntimeException;

final class Queue
{
    public const READY = 'queue:default';
    public const PROCESSING = 'queue:default:processing';
    public const DELAYED = 'queue:default:delayed';
    public const DEAD = 'queue:default:dead';
    private Redis $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', (int) (getenv('REDIS_PORT') ?: 6380));
    }

    public function push(string $type, array $payload): string
    {
        $id = bin2hex(random_bytes(8));
        $job = ['id' => $id, 'type' => $type, 'payload' => $payload, 'attempts' => 0,
            'created_at' => gmdate(DATE_ATOM)];
        $this->redis->lPush(self::READY, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $id;
    }

    public function reserve(int $timeout = 2): ?array
    {
        $raw = $this->redis->blMove(self::READY, self::PROCESSING, 'RIGHT', 'LEFT', $timeout);
        return $raw === false ? null : [json_decode($raw, true, 512, JSON_THROW_ON_ERROR), $raw];
    }

    public function ack(string $raw): void
    {
        if ($this->redis->lRem(self::PROCESSING, $raw, 1) !== 1) {
            throw new RuntimeException('Задача отсутствует в processing');
        }
    }

    public function fail(array $job, string $raw, string $error): array
    {
        $job['attempts'] = (int) $job['attempts'] + 1;
        $job['error'] = $error;
        $next = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        // Удаление из processing и отправка на повтор/в dead выполняются атомарно.
        $script = <<<'LUA'
if redis.call('LREM', KEYS[1], 1, ARGV[1]) ~= 1 then return 0 end
if ARGV[3] == 'dead' then redis.call('LPUSH', KEYS[3], ARGV[2])
else redis.call('ZADD', KEYS[2], ARGV[4], ARGV[2]) end
return 1
LUA;
        $dead = $job['attempts'] >= 3;
        $delay = 2 ** $job['attempts'];
        $ok = $this->redis->eval($script, [self::PROCESSING, self::DELAYED, self::DEAD,
            $raw, $next, $dead ? 'dead' : 'delayed', time() + $delay], 3);
        if ($ok !== 1) {
            throw new RuntimeException('Не удалось перенести ошибочную задачу');
        }
        return ['dead' => $dead, 'delay' => $dead ? 0 : $delay];
    }

    public function releaseDelayed(): int
    {
        $script = <<<'LUA'
local jobs = redis.call('ZRANGEBYSCORE', KEYS[1], '-inf', ARGV[1])
for _, job in ipairs(jobs) do
  redis.call('ZREM', KEYS[1], job)
  redis.call('LPUSH', KEYS[2], job)
end
return #jobs
LUA;
        return (int) $this->redis->eval($script, [self::DELAYED, self::READY, time()], 2);
    }

    public function recover(): int
    {
        $count = 0;
        while ($this->redis->lMove(self::PROCESSING, self::READY, 'RIGHT', 'LEFT') !== false) {
            $count++;
        }
        return $count;
    }

    public function stats(): array
    {
        return ['waiting' => $this->redis->lLen(self::READY),
            'processing' => $this->redis->lLen(self::PROCESSING),
            'delayed' => $this->redis->zCard(self::DELAYED),
            'dead' => $this->redis->lLen(self::DEAD)];
    }

    public function deadJobs(): array
    {
        return array_map(fn (string $raw): array => json_decode($raw, true, 512, JSON_THROW_ON_ERROR),
            $this->redis->lRange(self::DEAD, 0, -1));
    }
}
