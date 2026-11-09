<?php
require __DIR__.'/bootstrap.php';
$redis=\App\Core\Cache::redis();
if ($redis) echo 'Redis отвечает: PONG; версия '.$redis->info('server')['redis_version']."\n";
else echo "Redis недоступен; приложение работает без кеша (BYPASS).\n";
