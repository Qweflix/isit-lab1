<?php
require __DIR__.'/bootstrap.php';
if (!in_array('--confirm',$argv,true)) {fwrite(STDERR,"Только для учебной БД: добавьте --confirm.\n");exit(1);}
\App\Core\Database::connection()->exec(file_get_contents(BASE_PATH.'/sql/bigdata.sql'));
\App\Core\Cache::clear();
echo 'Заявок в БД: '.\App\Core\Database::connection()->query('SELECT count(*) FROM tickets')->fetchColumn()."; кеш очищен.\n";
