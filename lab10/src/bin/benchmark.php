<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\{Cache, Database};
$options=getopt('', ['url:','requests:','parallel:','concurrency:','output:']);
$base=rtrim($options['url']??'http://127.0.0.1:8000','/');
$n=max(10,(int)($options['requests']??30));$parallel=max(10,(int)($options['parallel']??40));$concurrency=max(1,min(8,(int)($options['concurrency']??4)));
$password=getenv('DEMO_PASSWORD');
if (!$password || !config('debug')) {fwrite(STDERR,"Нужны DEMO_PASSWORD и APP_DEBUG=1 (только для измерений).\n");exit(1);}
if (!Cache::redis()) throw new RuntimeException('Для сравнения нужен Redis');
$count=(int)Database::connection()->query('SELECT count(*) FROM tickets')->fetchColumn();
if ($count<100000) throw new RuntimeException('Загрузите bigdata: нужно не менее 100000 заявок');
function handle(string $url,array $headers=[],?string $body=null): CurlHandle {
    $c=curl_init($url);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers]);
    if($body!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body]);
    return $c;
}
function request(string $path,bool $off=false): array {
    global $base,$token;
    $headers=['Authorization: Bearer '.$token];if($off)$headers[]='X-Cache-Mode: off';
    $c=handle($base.$path,$headers);$cache='';
    curl_setopt($c,CURLOPT_HEADERFUNCTION,function($c,$line)use(&$cache){if(stripos($line,'X-Cache:')===0)$cache=trim(substr($line,8));return strlen($line);});
    $body=curl_exec($c);$code=curl_getinfo($c,CURLINFO_RESPONSE_CODE);$time=curl_getinfo($c,CURLINFO_TOTAL_TIME)*1000;
    if($body===false||$code!==200)throw new RuntimeException('HTTP '.$code.' в '.$path);
    return ['ms'=>$time,'cache'=>$cache,'sha256'=>hash('sha256',$body)];
}
function series(string $path,bool $off,int $n): array {
    $times=[];$headers=[];$hash=null;
    for($i=0;$i<$n;$i++){$r=request($path,$off);$times[]=$r['ms'];$headers[$r['cache']]=($headers[$r['cache']]??0)+1;$hash??=$r['sha256'];if($hash!==$r['sha256'])throw new RuntimeException('Изменились данные во время замера');}
    sort($times);return ['mean_ms'=>array_sum($times)/$n,'p95_ms'=>$times[(int)ceil($n*.95)-1],'headers'=>$headers,'sha256'=>$hash,'samples_ms'=>$times];
}
function throughput(string $path,bool $off,int $count,int $concurrency): array {
    global $base,$token;
    $multi=curl_multi_init();$handles=[];$sent=0;$done=0;$started=hrtime(true);
    $add=function()use($path,$off,$multi,&$handles,&$sent,$base,$token){
        $headers=['Authorization: Bearer '.$token];if($off)$headers[]='X-Cache-Mode: off';
        $c=handle($base.$path,$headers);$handles[spl_object_id($c)]=$c;curl_multi_add_handle($multi,$c);$sent++;
    };
    for($i=0;$i<min($concurrency,$count);$i++)$add();
    do {
        do{$status=curl_multi_exec($multi,$running);}while($status===CURLM_CALL_MULTI_PERFORM);
        while($info=curl_multi_info_read($multi)){
            $c=$info['handle'];
            if($info['result']!==CURLE_OK||curl_getinfo($c,CURLINFO_RESPONSE_CODE)!==200)throw new RuntimeException('Ошибка параллельного запроса');
            curl_multi_remove_handle($multi,$c);unset($handles[spl_object_id($c)]);$done++;
            if($sent<$count)$add();
        }
        if($done<$count)curl_multi_select($multi,0.1);
    }while($done<$count);
    $seconds=(hrtime(true)-$started)/1e9;curl_multi_close($multi);
    return ['requests'=>$done,'seconds'=>$seconds,'rps'=>$done/$seconds];
}
$c=handle($base.'/api/auth/login',['Content-Type: application/json'],json_encode(['email'=>'manager@example.test','password'=>$password]));
$login=json_decode(curl_exec($c),true);$token=$login['token']??throw new RuntimeException('Не удалось войти');
$scenarios=[
    'Категории'=>'/api/categories',
    'Карточка заявки'=>'/api/tickets/1',
    'Список и COUNT'=>'/api/tickets?limit=100&sort=id&order=desc',
    'Поиск подстроки'=>'/api/tickets?q='.rawurlencode('сетевого').'&limit=100',
    'Сводный отчёт'=>'/api/reports/summary',
    // Два своих сценария сверх пяти из методички.
    'Фильтр статуса'=>'/api/tickets?status=in_progress&category_id=2&limit=100',
    'Сортировка по теме'=>'/api/tickets?sort=title&order=asc&limit=100',
];
$result=['at'=>gmdate('c'),'tickets'=>$count,'php'=>PHP_VERSION,'postgres'=>Database::connection()->getAttribute(PDO::ATTR_SERVER_VERSION),'redis'=>Cache::redis()->info('server')['redis_version'],'workers'=>getenv('PHP_CLI_SERVER_WORKERS'),'sequential_requests'=>$n,'parallel_requests'=>$parallel,'concurrency'=>$concurrency,'scenarios'=>[]];
printf("Заявок: %d; PHP %s; PostgreSQL %s; Redis %s; workers=%s\n",$count,PHP_VERSION,$result['postgres'],$result['redis'],$result['workers']);
printf("Последовательно: %d; параллельно: %d, concurrency=%d\n",$n,$parallel,$concurrency);
printf("%-36s %10s %10s %10s %10s %9s\n",'Сценарий','OFF mean','HIT mean','OFF p95','HIT p95','ускорение');
foreach($scenarios as $name=>$path){
    Cache::clear();
    $off=series($path,true,$n);$offLoad=throughput($path,true,$parallel,$concurrency);
    $cold=request($path);$warm=series($path,false,$n);$warmLoad=throughput($path,false,$parallel,$concurrency);
    if($cold['cache']!=='MISS'||($warm['headers']['HIT']??0)!==$n||($off['headers']['BYPASS']??0)!==$n)throw new RuntimeException('Неверный режим кеша: '.$name);
    if($off['sha256']!==$warm['sha256'])throw new RuntimeException('Ответы OFF/HIT различаются');
    $row=['name'=>$name,'path'=>$path,'off'=>$off,'cold'=>$cold,'warm'=>$warm,'off_load'=>$offLoad,'warm_load'=>$warmLoad,'speedup'=>$off['mean_ms']/$warm['mean_ms']];
    $result['scenarios'][]=$row;
    printf("%-36s %10.3f %10.3f %10.3f %10.3f %8.2fx\n",$name,$off['mean_ms'],$warm['mean_ms'],$off['p95_ms'],$warm['p95_ms'],$row['speedup']);
    printf("  cold=MISS %.3f ms; warm=%d HIT; RPS %.1f -> %.1f\n",$cold['ms'],$n,$offLoad['rps'],$warmLoad['rps']);
}
$c=handle($base.'/api/auth/logout',['Authorization: Bearer '.$token]);curl_setopt($c,CURLOPT_POST,true);curl_exec($c);
if(isset($options['output']))file_put_contents($options['output'],json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
echo "Завершено: все ответы 200, OFF=HIT по содержимому, первый запрос MISS, повторные HIT.\n";
