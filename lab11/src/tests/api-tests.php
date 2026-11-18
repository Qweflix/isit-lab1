<?php
declare(strict_types=1);
require __DIR__ . '/../bin/bootstrap.php';

use App\Core\Database;
use App\Core\Queue;
use App\Jobs\NotificationJob;
use App\Jobs\Jobs;

$passed = 0;
function check(bool $condition, string $label): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $passed++;
    echo "PASS $label\n";
}
function request(string $method, string $path, ?array $body = null, ?string $user = 'alexey@example.test', array $headers = []): array
{
    $lines = ['Content-Type: application/json'];
    if ($user !== null) {
        $lines[] = 'Authorization: Basic ' . base64_encode($user . ':lab11-demo');
    }
    foreach ($headers as $key => $value) {
        $lines[] = "$key: $value";
    }
    $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $lines),
        'content' => $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE),
        'ignore_errors' => true, 'timeout' => 30]]);
    $text = file_get_contents((getenv('TEST_URL') ?: 'http://app:8000') . $path, false, $context);
    if ($text === false) {
        throw new RuntimeException('HTTP недоступен: ' . $path);
    }
    $status = (int) (explode(' ', $http_response_header[0])[1] ?? 0);
    return [$status, json_decode($text, true), $http_response_header, $text];
}
function headerValue(array $headers, string $name): ?string
{
    foreach ($headers as $line) {
        if (str_starts_with(strtolower($line), strtolower($name) . ':')) {
            return trim(substr($line, strlen($name) + 1));
        }
    }
    return null;
}
$pdo = Database::connection();
$queue = new Queue();
check((int) $pdo->query('SELECT count(*) FROM tickets')->fetchColumn() >= 6, 'начальные заявки PostgreSQL');
check((new Redis())->connect(getenv('REDIS_HOST') ?: 'redis', (int) (getenv('REDIS_PORT') ?: 6379)), 'Redis доступен');
[$code, $tickets] = request('GET', '/api/tickets');
check($code === 200 && count($tickets) >= 6, 'старый API заявок');
[$code] = request('POST', '/api/reports', [], null);
check($code === 401, 'отчёт требует авторизацию');
[$code, $before] = request('GET', '/api/tickets/1');
check($code === 200 && $before['id'] === 1, 'чтение заявки');
$target = $before['status'] === 'done' ? 'new' : 'done';
$waitingBefore = $queue->stats()['waiting'];
[$code, $updated, $headers] = request('PATCH', '/api/tickets/1', ['status' => $target]);
$jobId = headerValue($headers, 'X-Job-Id');
check($code === 200 && $updated['status'] === $target && strlen($jobId ?? '') === 16, 'смена статуса ставит задачу');
check($queue->stats()['waiting'] === $waitingBefore + 1, 'задача ждёт в Redis');
[$code, , $headers] = request('PATCH', '/api/tickets/1', ['title' => $before['title']]);
check($code === 200 && headerValue($headers, 'X-Job-Id') === null, 'правка без смены статуса не ставит задачу');
[$code, $report, $headers] = request('POST', '/api/reports', [], 'operator@example.test');
$reportId = $report['id'] ?? 0;
check($code === 202 && headerValue($headers, 'Location') === '/api/reports/' . $reportId, 'отчёт принят с Location');
[$code, $state] = request('GET', '/api/reports/' . $reportId, null, 'operator@example.test');
check($code === 200 && $state['status'] === 'queued', 'статус до воркера queued');
$pdo->exec("INSERT INTO staff (name, email, role, password_hash) VALUES ('Другой оператор', 'operator2@example.test', 'Оператор', crypt('lab11-demo', gen_salt('bf'))) ON CONFLICT (email) DO NOTHING");
[$code] = request('GET', '/api/reports/' . $reportId, null, 'operator2@example.test');
check($code === 403, 'чужой отчёт закрыт от другого оператора');
[$code] = request('GET', '/api/reports/' . $reportId);
check($code === 200, 'руководитель видит отчёт');
[$code] = request('GET', '/api/reports/' . $reportId . '/download', null, 'operator@example.test');
check($code === 409, 'скачивание до готовности запрещено');
$raw = $queue->reserve(1);
check($raw !== null, 'BLMOVE резервирует задание');
[$job, $message] = $raw;
if ($job['type'] === 'notification') {
    (new NotificationJob())->handle($job['payload'], $job['id']);
    (new NotificationJob())->handle($job['payload'], $job['id']);
    $count = $pdo->prepare('SELECT count(*) FROM notifications WHERE job_id = ?');
    $count->execute([$job['id']]);
    check((int) $count->fetchColumn() === 2, 'повтор уведомления не создаёт дубли');
    $text = $pdo->prepare('SELECT body FROM notifications WHERE job_id = ? LIMIT 1');
    $text->execute([$job['id']]);
    check(str_contains($text->fetchColumn(), $before['title']), 'уведомление содержит название заявки');
    $author = $pdo->prepare('SELECT count(*) FROM notifications WHERE job_id = ? AND staff_id = 1');
    $author->execute([$job['id']]);
    check((int) $author->fetchColumn() === 0, 'автор не получает уведомление');
}
$queue->ack($message);
check($queue->stats()['processing'] === 0, 'ack очищает processing');
$raw = $queue->reserve(1);
check($raw !== null && $raw[0]['type'] === 'report', 'воркер получает задание отчёта');
Jobs::handle($raw[0]);
$queue->ack($raw[1]);
[$code, $state] = request('GET', '/api/reports/' . $reportId, null, 'operator@example.test');
check($code === 200 && $state['status'] === 'done', 'отчёт завершён воркером');
[$code, , , $csv] = request('GET', '/api/reports/' . $reportId . '/download', null, 'operator@example.test');
check($code === 200 && str_starts_with($csv, "\xEF\xBB\xBF") && str_contains($csv, 'Заявка'), 'CSV отдаётся с BOM и кириллицей');
$savedCsv = file_get_contents(BASE_PATH . '/storage/reports/report-' . $reportId . '.csv');
Jobs::handle($raw[0]);
check(file_get_contents(BASE_PATH . '/storage/reports/report-' . $reportId . '.csv') === $savedCsv,
    'повтор готового отчёта не меняет CSV');
[$code, $sync] = request('POST', '/api/reports', [], 'alexey@example.test', ['X-Queue-Mode' => 'sync']);
check($code === 200 && $sync['status'] === 'done', 'синхронный режим формирует отчёт в запросе');
check($queue->stats()['waiting'] === 0, 'синхронный режим не кладёт задание в очередь');
$first = $queue->push('demo', ['mode' => 'normal']);
$second = $queue->push('demo', ['mode' => 'normal']);
$one = $queue->reserve(1);
$two = $queue->reserve(1);
check($one[0]['id'] === $first && $two[0]['id'] === $second, 'очередь сохраняет FIFO');
$queue->ack($one[1]);
$queue->ack($two[1]);
$failedId = (int) $pdo->query('INSERT INTO reports (requester_id) VALUES (1) RETURNING id')->fetchColumn();
$reportsDir = BASE_PATH . '/storage/reports';
$backupDir = BASE_PATH . '/storage/reports-check-backup';
if (!rename($reportsDir, $backupDir)) {
    throw new RuntimeException('Не удалось подготовить проверку ошибки записи CSV');
}
try {
    file_put_contents($reportsDir, 'blocked');
    try {
        (new App\Jobs\ReportJob())->handle(['report_id' => $failedId], bin2hex(random_bytes(8)));
    } catch (Throwable $expected) {
        // Ошибка записи должна попасть в reports.error.
    }
} finally {
    unlink($reportsDir);
    rename($backupDir, $reportsDir);
}
$failed = $pdo->query('SELECT status, error FROM reports WHERE id = ' . $failedId)->fetch();
check($failed['status'] === 'failed' && $failed['error'] !== '', 'ошибка CSV сохраняет статус failed и причину');
echo "ИТОГО: $passed проверок успешно\n";
