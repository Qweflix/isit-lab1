<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use App\Core\Queue;
use App\Jobs\Jobs;

$drain = in_array('--drain', $argv, true);
$running = true;
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, function () use (&$running): void { $running = false; });
    pcntl_signal(SIGINT, function () use (&$running): void { $running = false; });
}
$queue = new Queue();
$recovered = $queue->recover();
echo "Восстановлено в очередь: $recovered\n";
while ($running) {
    $queue->releaseDelayed();
    $reserved = $queue->reserve($drain ? 1 : 2);
    if ($reserved === null) {
        $stats = $queue->stats();
        if ($drain && $stats['waiting'] === 0 && $stats['delayed'] === 0) {
            break;
        }
        continue;
    }
    [$job, $raw] = $reserved;
    $started = microtime(true);
    try {
        Jobs::handle($job);
        $queue->ack($raw);
        echo sprintf("%s Готово %s (%s) за %.2f с\n", gmdate(DATE_ATOM), $job['id'],
            $job['type'], microtime(true) - $started);
    } catch (Throwable $error) {
        $result = $queue->fail($job, $raw, $error->getMessage());
        echo sprintf("%s Ошибка %s: %s; %s\n", gmdate(DATE_ATOM), $job['id'],
            $error->getMessage(), $result['dead'] ? 'мёртвая очередь' : 'повтор через ' . $result['delay'] . ' с');
    }
    flush();
}
echo "Воркер остановлен\n";
