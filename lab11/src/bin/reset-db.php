<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$pdo = App\Core\Database::connection();
$pdo->exec('DROP TABLE IF EXISTS notifications, reports');
foreach (['schema.sql', 'seed.sql', 'async.sql'] as $file) {
    $pdo->exec(file_get_contents(BASE_PATH . '/sql/' . $file));
}
$redis = new Redis();
$redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', (int) (getenv('REDIS_PORT') ?: 6380));
foreach ([App\Core\Queue::READY, App\Core\Queue::PROCESSING,
    App\Core\Queue::DELAYED, App\Core\Queue::DEAD] as $key) {
    $redis->del($key);
}
foreach (glob(BASE_PATH . '/storage/reports/report-*.csv') ?: [] as $file) {
    unlink($file);
}
foreach (glob(BASE_PATH . '/storage/reports/report-*.tmp') ?: [] as $file) {
    unlink($file);
}
echo "База и очередь сброшены\n";
