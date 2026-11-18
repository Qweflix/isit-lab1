<?php
declare(strict_types=1);
namespace App\Jobs;

final class Jobs
{
    public static function handle(array $job): void
    {
        $handler = match ($job['type']) {
            'notification' => new NotificationJob(),
            'report' => new ReportJob(),
            'demo' => new DemoJob(),
            default => throw new \RuntimeException('Неизвестный тип задачи: ' . $job['type']),
        };
        $handler->handle($job['payload'], $job['id'], (int) $job['attempts']);
    }
}
