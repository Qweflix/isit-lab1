<?php
declare(strict_types=1);
namespace App\Jobs;

final class DemoJob
{
    public function handle(array $payload, string $jobId, int $attempts): void
    {
        if (($payload['mode'] ?? '') === 'normal') {
            sleep(1);
        }
        if (($payload['mode'] ?? '') === 'slow') {
            sleep((int) ($payload['seconds'] ?? 20));
        }
        if (($payload['mode'] ?? '') === 'fail' ||
            (($payload['mode'] ?? '') === 'flaky' && $attempts < 2)) {
            throw new \RuntimeException('Демонстрационная ошибка');
        }
        echo "Готово: $jobId\n";
    }
}
