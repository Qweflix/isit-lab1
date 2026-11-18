<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use App\Core\Queue;

$queue = new Queue();
$before = $queue->stats();
$id = $queue->push('demo', ['mode' => 'normal']);
$afterPush = $queue->stats();
[$job, $raw] = $queue->reserve(1) ?? throw new RuntimeException('Задача не получена');
$afterReserve = $queue->stats();
if ($job['id'] !== $id) {
    throw new RuntimeException('Получена другая задача: очистите учебную очередь');
}
$queue->ack($raw);
$afterAck = $queue->stats();
if ($afterPush['waiting'] !== $before['waiting'] + 1 ||
    $afterReserve['processing'] !== $before['processing'] + 1 ||
    $afterAck['processing'] !== $before['processing']) {
    throw new RuntimeException('Неверное состояние очереди');
}
echo "Очередь работает: push, BLMOVE, ack\n";
echo json_encode(['push' => $afterPush, 'reserve' => $afterReserve, 'ack' => $afterAck], JSON_PRETTY_PRINT) . "\n";
