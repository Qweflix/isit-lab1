<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
if (!in_array('--confirm', $argv,true)) { fwrite(STDERR,"Сброс удаляет данные отдельной учебной БД. Добавьте --confirm.\n"); exit(1); }
define('BASE_PATH',dirname(__DIR__)); define('APP_PATH',BASE_PATH.'/app');
require APP_PATH.'/autoload.php'; require APP_PATH.'/helpers.php';
$pdo=\App\Core\Database::connection();
foreach (['schema','seed'] as $file) $pdo->exec(file_get_contents(BASE_PATH.'/sql/'.$file.'.sql'));
// Пароль задаётся локально. Открытые пароли и токены в репозиторий не записываются.
$password=getenv('DEMO_PASSWORD');
if (!$password || strlen($password)<8) { fwrite(STDERR,"Задайте DEMO_PASSWORD длиной не менее 8 символов.\n"); exit(1); }
$s=$pdo->prepare('INSERT INTO users(staff_id,name,email,password_hash,role) VALUES (:staff,:name,:email,:hash,:role)');
foreach ([[1,'Алексей Смирнов','manager@example.test','manager'],[2,'Мария Десяк','operator@example.test','operator']] as [$id,$name,$email,$role]) {
    $s->execute(['staff'=>$id,'name'=>$name,'email'=>$email,'hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>$role]);
}
echo "Созданы 7 таблиц ЛР8, users и auth_tokens; 6 заявок, 2 пользователя.\n";
