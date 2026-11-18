<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$redis = new Redis();
$redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', (int) (getenv('REDIS_PORT') ?: 6380));
echo 'Redis ' . $redis->ping() . ' ' . $redis->info('server')['redis_version'] . "\n";
