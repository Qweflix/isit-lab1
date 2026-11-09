<?php
declare(strict_types=1);
// Запускать только на отдельной учебной БД после reset-db.php.
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000','/');
$password = getenv('DEMO_PASSWORD');
if (!$password) { fwrite(STDERR,"Задайте DEMO_PASSWORD.\n"); exit(1); }
define('BASE_PATH',dirname(__DIR__));define('APP_PATH',BASE_PATH.'/app');
require APP_PATH.'/autoload.php';require APP_PATH.'/helpers.php';
$results=[]; $failed=0;
function callApi(string $method,string $path,mixed $body=null,?string $token=null, string $extraHeaders=''): array {
    global $base;
    $headers="Accept: application/json\r\n".$extraHeaders;
    if($token!==null)$headers.="Authorization: Bearer $token\r\n";
    if($body!==null)$headers.="Content-Type: application/json\r\n";
    $context=stream_context_create(['http'=>['method'=>$method,'header'=>$headers,'content'=>$body===null?'':(is_string($body)?$body:json_encode($body)),'ignore_errors'=>true,'timeout'=>10]]);
    $text=file_get_contents($base.$path,false,$context);
    if($text===false)throw new RuntimeException('Нет связи с сервером');
    preg_match('/\s(\d{3})\s/',$http_response_header[0],$match);
    return ['status'=>(int)$match[1],'data'=>json_decode($text,true),'body'=>$text,'headers'=>$http_response_header];
}
function check(string $name,bool $ok): void {
    global $failed,$results;
    $results[]=['test'=>$name,'passed'=>$ok]; if(!$ok)$failed++;
    echo ($ok?'PASS':'FAIL')." $name\n";
}
function expect(string $name,string $method,string $path,int $status,mixed $body=null,?string $token=null): array {
    $r=callApi($method,$path,$body,$token);check($name,$r['status']===$status);return $r;
}
$login=expect('Вход руководителя','POST','/api/auth/login',200,['email'=>'manager@example.test','password'=>$password]);$manager=$login['data']['token'];
check('Открытый токен: 64 hex',preg_match('/^[a-f0-9]{64}$/D',$manager)===1);
check('Срок токена около 2 часов',abs(strtotime($login['data']['expires_at'])-time()-7200)<10);
check('В ответе входа нет password_hash',!str_contains($login['body'],'password_hash'));
$operator=expect('Вход оператора','POST','/api/auth/login',200,['email'=>'operator@example.test','password'=>$password])['data']['token'];
foreach(['/api/tickets','/api/categories','/api/staff','/api/auth/me','/api/unknown'] as $p)expect('Без токена '.$p,'GET',$p,401);
expect('Поддельный токен','GET','/api/tickets',401,null,str_repeat('a',64));
$bad=expect('Неверный пароль','POST','/api/auth/login',401,['email'=>'manager@example.test','password'=>'wrong']);
$unknown=expect('Неизвестная почта','POST','/api/auth/login',401,['email'=>'absent@example.test','password'=>'wrong']);
check('Одинаковая ошибка почты и пароля',$bad['body']===$unknown['body']);
expect('SQL-инъекция при входе','POST','/api/auth/login',401,['email'=>"' OR 1=1 --",'password'=>$password]);
expect('Некорректный JSON','POST','/api/auth/login',400,'{');
expect('Массив вместо объекта','POST','/api/auth/login',400,'[]');
expect('Неверные типы входа','POST','/api/auth/login',422,['email'=>[], 'password'=>1]);
$me=expect('Пользователь /me','GET','/api/auth/me',200,null,$manager);
check('Нет пароля и хеша в /me',!str_contains($me['body'],'password'));
foreach(['tickets','categories','staff'] as $resource){
 $r=expect('Список '.$resource,'GET','/api/'.$resource,200,null,$operator);
 check('Нет хеша в '.$resource,!str_contains($r['body'],'password_hash'));
 expect('Одна запись '.$resource,'GET','/api/'.$resource.'/1',200,null,$manager);
 expect('Несуществующая запись '.$resource,'GET','/api/'.$resource.'/999999',404,null,$manager);
 expect('Запрет удаления '.$resource,'DELETE','/api/'.$resource.'/1',403,null,$operator);
}
$r=expect('Начальные 6 заявок','GET','/api/tickets',200,null,$manager);check('Количество равно 6',count($r['data'])===6);
$r=expect('Поиск кириллицей','GET','/api/tickets?q='.urlencode('КОМПЬЮТЕР'),200,null,$manager);check('Поиск без учёта регистра',count($r['data'])===1);
$r=expect('SQL-инъекция в поиске','GET','/api/tickets?q='.urlencode("' OR 1=1 --"),200,null,$manager);check('Инъекция не возвращает строки',$r['data']===[]);
foreach(['status=bad','category_id=x','sort=id%3BDROP%20TABLE%20tickets','order=bad','q%5B%5D=x'] as $q)expect('Неверный фильтр '.$q,'GET','/api/tickets?'.$q,422,null,$manager);
$r=expect('Фильтр статуса и категории','GET','/api/tickets?status=done&category_id=2',200,null,$manager);check('Совместные фильтры',count($r['data'])===1 && $r['data'][0]['id']===6);
$r=expect('Сортировка по ID desc','GET','/api/tickets?sort=id&order=desc',200,null,$manager);check('ID в обратном порядке',array_column($r['data'],'id')===[6,5,4,3,2,1]);
expect('Список заявок категории','GET','/api/categories/1/tickets',200,null,$manager);
expect('Неизвестная категория связанных записей','GET','/api/categories/999999/tickets',404,null,$manager);
expect('Защита категории с заявками','DELETE','/api/categories/1',409,null,$manager);
expect('Защита сотрудника с зависимостями','DELETE','/api/staff/1',409,null,$manager);
expect('Оператор не создаёт справочник','POST','/api/categories',403,['title'=>'Категория','owner_id'=>1],$operator);
expect('Оператор не меняет сотрудников','PATCH','/api/staff/1',403,['name'=>'Новый сотрудник'],$operator);
expect('Пустая заявка','POST','/api/tickets',422,(object)[],$manager);
$data=['title'=>'Проверка API','description'=>'Учебная проверка','category_id'=>1,'due_date'=>null];
foreach([['category_id'=>'1'],['due_date'=>''],['due_date'=>'2026-02-30'],['assignee_id'=>99999],['priority'=>'bad']] as $invalid)expect('Неверное поле '.key($invalid),'POST','/api/tickets',422,array_replace($data,$invalid),$manager);
$r=expect('Создание оператором','POST','/api/tickets',201,$data,$operator);$id=$r['data']['id'];
check('Location новой заявки',in_array('Location: /api/tickets/'.$id,$r['headers'],true));
check('Дата null сохраняется',$r['data']['due_date']===null);
expect('Изменение оператором','PATCH','/api/tickets/'.$id,200,['status'=>'done','due_date'=>'2026-12-01'],$operator);
$r=expect('PUT сохраняет остальные поля','PUT','/api/tickets/'.$id,200,['title'=>'Изменённая заявка','id'=>99999],$manager);
check('Защита ID и сохранение статуса',$r['data']['id']===$id&&$r['data']['status']==='done');
expect('Удаление руководителем','DELETE','/api/tickets/'.$id,204,null,$manager);
expect('Удалённая запись недоступна','GET','/api/tickets/'.$id,404,null,$manager);
foreach(['categories'=>['title'=>'Тестовая категория','owner_id'=>1],'staff'=>['name'=>'Тестовый сотрудник','email'=>'test@example.test']] as $res=>$body){
 $r=expect('Создание '.$res,'POST','/api/'.$res,201,$body,$manager);$id=$r['data']['id'];
 expect('Дубликат '.$res,'POST','/api/'.$res,409,$body,$manager);
 expect('PATCH '.$res,'PATCH','/api/'.$res.'/'.$id,200,$res==='staff'?['role'=>'Оператор']:['description'=>'Изменено'],$manager);
 expect('PUT '.$res,'PUT','/api/'.$res.'/'.$id,200,$res==='staff'?['name'=>'Изменённый сотрудник']:['title'=>'Изменённая категория'],$manager);
 expect('Удаление '.$res,'DELETE','/api/'.$res.'/'.$id,204,null,$manager);
}
expect('Метод не разрешён','OPTIONS','/api/tickets',405,null,$manager);
expect('Неизвестный маршрут','GET','/api/nothing',404,null,$manager);
// Дополнительные проверки ЛР10 выполняются на той же небольшой учебной БД.
use App\Core\Cache;
function cacheHeader(array $response): string {
    foreach ($response['headers'] as $line) if (stripos($line,'X-Cache:')===0) return trim(substr($line,8));
    return '';
}
$redis=Cache::redis();check('Redis доступен',$redis!==null);
if (!$redis) { echo "Redis обязателен для кеш-тестов\n"; exit(1); }
Cache::clear();
Cache::set('test:false',false,10);$found=false;$value=Cache::get('test:false',$found);
check('Кеш сохраняет false',$found && $value===false);
Cache::set('test:empty',[],10);$found=false;$value=Cache::get('test:empty',$found);
check('Кеш сохраняет пустой массив',$found && $value===[]);
$ttl=$redis->ttl(Cache::key('test:empty'));check('TTL с разбросом 10–11 секунд',$ttl>=9&&$ttl<=11);
Cache::set('test:expire','value',1);usleep(1100000);$found=false;Cache::get('test:expire',$found);check('TTL удаляет запись',!$found);
$redis->setex(Cache::key('test:corrupt'),30,'not-json');
$value=Cache::remember('test:corrupt',30,fn()=>['restored'=>true]);
check('Повреждённый JSON безопасно заменяется',$value===['restored'=>true]&&Cache::status()==='MISS');
Cache::clear();
foreach(['/api/categories','/api/tickets/1','/api/tickets?limit=2&page=1','/api/reports/summary'] as $path){
 $a=callApi('GET',$path,null,$manager);$b=callApi('GET',$path,null,$manager);
 check('Первый MISS '.$path,$a['status']===200&&cacheHeader($a)==='MISS');
 check('Повторный HIT '.$path,$b['status']===200&&cacheHeader($b)==='HIT'&&$a['body']===$b['body']);
}
$a=callApi('GET','/api/tickets?page=1&limit=2',null,$manager);$b=callApi('GET','/api/tickets?limit=2&page=1',null,$manager);
check('Порядок query-параметров не меняет ключ',cacheHeader($a)==='HIT'&&$a['body']===$b['body']);
$a=callApi('GET','/api/tickets?limit=2&page=2',null,$manager);
check('Страница включена в ключ',cacheHeader($a)==='MISS'&&$a['data'][0]['id']===3);
foreach(['page=0','page=abc','limit=101','limit=0'] as $q)expect('Неверная пагинация '.$q,'GET','/api/tickets?'.$q,422,null,$manager);
$a=callApi('GET','/api/tickets',null,$manager,"X-Cache-Mode: off\r\n");
check('Отладочный обход кеша',cacheHeader($a)===(config('debug')?'BYPASS':'MISS') || (!config('debug')&&cacheHeader($a)==='HIT'));
// Прогрев всех зависимых значений перед изменением.
foreach(['/api/tickets','/api/reports/summary','/api/tickets/1'] as $path)callApi('GET',$path,null,$manager);
$created=callApi('POST','/api/tickets',['title'=>'Кеш-тест','description'=>'Проверка инвалидации','category_id'=>1],$manager);
$id=$created['data']['id'];check('POST для проверки кеша',$created['status']===201);
$a=callApi('GET','/api/tickets',null,$manager);$b=callApi('GET','/api/reports/summary',null,$manager);
check('POST инвалидирует список',cacheHeader($a)==='MISS'&&count($a['data'])===7);
check('POST инвалидирует отчёт',cacheHeader($b)==='MISS'&&array_sum(array_column($b['data'],'total'))===7);
callApi('GET','/api/tickets/'.$id,null,$manager);
callApi('PATCH','/api/tickets/'.$id,['title'=>'Новое название кеш-теста'],$manager);
$a=callApi('GET','/api/tickets/'.$id,null,$manager);check('PATCH инвалидирует карточку',cacheHeader($a)==='MISS'&&$a['data']['title']==='Новое название кеш-теста');
$a=callApi('GET','/api/tickets',null,$manager);check('PATCH инвалидирует список',cacheHeader($a)==='MISS');
$a=callApi('GET','/api/reports/summary',null,$manager);check('PATCH инвалидирует отчёт',cacheHeader($a)==='MISS');
callApi('DELETE','/api/tickets/'.$id,null,$manager);
$a=callApi('GET','/api/tickets/'.$id,null,$manager);check('DELETE не оставляет старую карточку',$a['status']===404&&cacheHeader($a)==='MISS');
$a=callApi('GET','/api/tickets',null,$manager);check('DELETE инвалидирует список',cacheHeader($a)==='MISS'&&count($a['data'])===6);
$a=callApi('GET','/api/reports/summary',null,$manager);check('DELETE инвалидирует отчёт',cacheHeader($a)==='MISS'&&array_sum(array_column($a['data'],'total'))===6);
callApi('GET','/api/categories',null,$manager);
$c=callApi('POST','/api/categories',['title'=>'Категория кеш-теста','owner_id'=>1],$manager);$cid=$c['data']['id'];
$a=callApi('GET','/api/categories',null,$manager);check('POST категории инвалидирует справочник',cacheHeader($a)==='MISS'&&count($a['data'])===4);
callApi('GET','/api/reports/summary',null,$manager);
callApi('PATCH','/api/categories/'.$cid,['title'=>'Переименованная категория'],$manager);
$a=callApi('GET','/api/categories',null,$manager);check('PATCH категории обновляет справочник',cacheHeader($a)==='MISS'&&end($a['data'])['title']==='Переименованная категория');
$a=callApi('GET','/api/reports/summary',null,$manager);check('PATCH категории обновляет названия отчёта',cacheHeader($a)==='MISS'&&end($a['data'])['category_title']==='Переименованная категория');
callApi('DELETE','/api/categories/'.$cid,null,$manager);
$a=callApi('GET','/api/categories',null,$manager);check('DELETE категории инвалидирует справочник',cacheHeader($a)==='MISS'&&count($a['data'])===3);
$a=callApi('GET','/api/tickets',null,$operator);check('Данные кеша доступны обеим разрешённым ролям',$a['status']===200);
$a=callApi('DELETE','/api/tickets/1',null,$operator);check('Прогретый кеш не обходит авторизацию',$a['status']===403);
// clear не затрагивает чужой префикс.
$redis->set('other-lab10-test:keep','ok');Cache::clear();
check('Очистка ограничена префиксом',$redis->get('other-lab10-test:keep')==='ok');$redis->del('other-lab10-test:keep');
expect('Выход отзывает токен','POST','/api/auth/logout',204,null,$operator);
expect('Отозванный токен','GET','/api/tickets',401,null,$operator);
$again=expect('Повторный вход','POST','/api/auth/login',200,['email'=>'operator@example.test','password'=>$password]);
expect('Выход после повторного входа','POST','/api/auth/logout',204,null,$again['data']['token']);
expect('Выход руководителя','POST','/api/auth/logout',204,null,$manager);
echo 'TOTAL '.count($results).'; FAILED '.$failed."\n";
if(getenv('TEST_REPORT'))file_put_contents(getenv('TEST_REPORT'),json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
exit($failed?1:0);
