<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$queue = new App\Core\Queue();
echo json_encode($queue->stats(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
if (in_array('--dead', $argv, true)) {
    echo json_encode($queue->deadJobs(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
