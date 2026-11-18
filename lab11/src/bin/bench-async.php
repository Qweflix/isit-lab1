<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$url = getenv('BENCH_URL') ?: 'http://app:8000';
$rows = [];
foreach (['async', 'sync'] as $mode) {
    for ($i = 1; $i <= 5; $i++) {
        $headers = ['Authorization: Basic ' . base64_encode('alexey@example.test:lab11-demo'),
            'Content-Type: application/json'];
        if ($mode === 'sync') {
            $headers[] = 'X-Queue-Mode: sync';
        }
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $headers),
            'content' => '{}', 'ignore_errors' => true]]);
        $start = microtime(true);
        $body = file_get_contents($url . '/api/reports', false, $context);
        $response = microtime(true);
        $status = (int) (explode(' ', $http_response_header[0])[1] ?? 0);
        $data = json_decode($body ?: '', true);
        if (!in_array($status, [200, 202], true) || !isset($data['id'])) {
            throw new RuntimeException('Заказ отчёта не выполнен: ' . $body);
        }
        $id = (int) $data['id'];
        while ($data['status'] !== 'done') {
            usleep(50000);
            $read = stream_context_create(['http' => ['header' => 'Authorization: Basic ' . base64_encode('alexey@example.test:lab11-demo')]]);
            $data = json_decode(file_get_contents($url . '/api/reports/' . $id, false, $read), true);
            if ($data['status'] === 'failed' || microtime(true) - $start > 60) {
                throw new RuntimeException('Отчёт не готов: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
        $finish = microtime(true);
        $rows[] = ['mode' => $mode, 'run' => $i, 'report_id' => $id,
            'http_ms' => round(($response - $start) * 1000, 2),
            'wait_ms' => round(($finish - $response) * 1000, 2),
            'total_ms' => round(($finish - $start) * 1000, 2)];
    }
}
$path = getenv('BENCH_OUTPUT') ?: '/lab11-docs/evidence/benchmark.json';
if (!is_dir(dirname($path))) {
    mkdir(dirname($path), 0775, true);
}
file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "Режим;Прогон;Отчёт;HTTP, мс;Ожидание, мс;Всего, мс\n";
foreach ($rows as $row) {
    echo implode(';', array_values($row)) . "\n";
}
