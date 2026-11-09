<?php
require dirname(__DIR__).'/bin/bootstrap.php';
use App\Core\Cache;
$_SERVER['HTTP_X_CACHE_MODE']='off';$calls=0;
$loader=function()use(&$calls){$calls++;return 'ok';};
$key='test:config:'.bin2hex(random_bytes(8));
Cache::remember($key,10,$loader);Cache::remember($key,10,$loader);
if (!config('cache')['enabled']) {
    if ($calls!==2||Cache::status()!=='BYPASS')throw new RuntimeException('Disabled cache used');
    echo "PASS CACHE_ENABLED=0: оба вызова BYPASS, источник вызван дважды\n";
} else {
    if(config('debug')||$calls!==1||Cache::status()!=='HIT')throw new RuntimeException('Production bypass allowed');
    echo "PASS APP_DEBUG=0: X-Cache-Mode off игнорируется, второй вызов HIT\n";
}
