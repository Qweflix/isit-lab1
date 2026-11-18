<?php
require __DIR__.'/bootstrap.php';
use App\Core\Cache;
Cache::clear();
for ($i=1;$i<=2;$i++) {
    $start=hrtime(true);
    $value=Cache::remember('demo:slow',30,function(){usleep(1000000);return ['result'=>'Данные из медленного источника'];});
    printf("Вызов %d: %s, %.3f мс; %s\n",$i,Cache::status(),(hrtime(true)-$start)/1e6,$value['result']);
}
$redis=Cache::redis();if($redis) echo 'TTL demo:slow = '.$redis->ttl(Cache::key('demo:slow'))." секунд\n";
