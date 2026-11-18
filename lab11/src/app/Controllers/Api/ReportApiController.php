<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Queue;
use App\Jobs\ReportJob;

final class ReportApiController
{
    public function store(): void
    {
        $user = Auth::user();
        $pdo = Database::connection();
        $query = $pdo->prepare('INSERT INTO reports (requester_id) VALUES (?) RETURNING id');
        $query->execute([$user['id']]);
        $id = (int) $query->fetchColumn();
        $payload = ['report_id' => $id];
        $sync = strtolower($_SERVER['HTTP_X_QUEUE_MODE'] ?? '') === 'sync';
        try {
            $jobId = $sync ? bin2hex(random_bytes(8)) : (new Queue())->push('report', $payload);
        } catch (\Throwable $error) {
            $pdo->prepare("UPDATE reports SET status = 'failed', error = ? WHERE id = ?")
                ->execute([$error->getMessage(), $id]);
            throw $error;
        }
        header('Location: /api/reports/' . $id);
        header('X-Job-Id: ' . $jobId);
        if ($sync) {
            (new ReportJob())->handle($payload, $jobId);
            json_response($this->find((string) $id), 200);
        }
        json_response(['id' => $id, 'status' => 'queued'], 202);
    }

    private function find(string $id): array
    {
        $user = Auth::user();
        $query = Database::connection()->prepare('SELECT * FROM reports WHERE id = ?');
        $query->execute([record_id($id)]);
        $row = $query->fetch();
        if (!$row) {
            json_error('Запись не найдена', 404);
        }
        if ((int) $row['requester_id'] !== $user['id'] && !Auth::isSupervisor($user['id'])) {
            json_error('Нет доступа к отчёту', 403);
        }
        $row['id'] = (int) $row['id'];
        $row['requester_id'] = (int) $row['requester_id'];
        $row['download_url'] = $row['status'] === 'done' ? '/api/reports/' . $id . '/download' : null;
        return $row;
    }

    public function show(string $id): void
    {
        json_response($this->find($id));
    }

    public function download(string $id): void
    {
        $report = $this->find($id);
        if ($report['status'] !== 'done') {
            json_error('Отчёт ещё не готов', 409);
        }
        $path = BASE_PATH . '/storage/reports/' . basename($report['path']);
        if (!is_file($path)) {
            json_error('Файл отчёта отсутствует', 500);
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report-' . $report['id'] . '.csv"');
        readfile($path);
    }
}
