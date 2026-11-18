<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$pdo = App\Core\Database::connection();
$before = (int) $pdo->query('SELECT count(*) FROM tickets')->fetchColumn();
$pdo->exec(file_get_contents(BASE_PATH . '/sql/bigdata.sql'));
$after = (int) $pdo->query('SELECT count(*) FROM tickets')->fetchColumn();
echo "Заявок до: $before; после: $after\n";
