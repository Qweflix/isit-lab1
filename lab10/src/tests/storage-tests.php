<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__));define('APP_PATH',BASE_PATH.'/app');
require APP_PATH.'/autoload.php';require APP_PATH.'/helpers.php';
$password=getenv('DEMO_PASSWORD');
if(!$password){fwrite(STDERR,"Задайте DEMO_PASSWORD\n");exit(1);}
$pdo=\App\Core\Database::connection();
$hash=$pdo->query("SELECT password_hash FROM users WHERE email='manager@example.test'")->fetchColumn();
if($hash===$password || !password_verify($password,$hash))throw new RuntimeException('Password storage failed');
echo "PASS Пароль хранится хешем и проверяется password_verify\n";
$base=rtrim($argv[1]??'http://127.0.0.1:8000','/');
$ctx=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>json_encode(['email'=>'manager@example.test','password'=>$password])]]);
$login=json_decode(file_get_contents($base.'/api/auth/login',false,$ctx),true,512,JSON_THROW_ON_ERROR);
$token=$login['token'];$s=$pdo->prepare('SELECT count(*) FROM auth_tokens WHERE token_hash=:hash');
$s->execute(['hash'=>hash('sha256',$token)]);if((int)$s->fetchColumn()!==1)throw new RuntimeException('Hash missing');
echo "PASS В БД найден sha256 выданного токена\n";
$s->execute(['hash'=>$token]);if((int)$s->fetchColumn()!==0)throw new RuntimeException('Raw token stored');
echo "PASS Открытый токен в БД отсутствует\n";
$s=$pdo->prepare('DELETE FROM auth_tokens WHERE token_hash=:hash');$s->execute(['hash'=>hash('sha256',$token)]);
echo "TOTAL 3; FAILED 0\n";
