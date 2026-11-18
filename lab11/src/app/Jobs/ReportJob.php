<?php
declare(strict_types=1);
namespace App\Jobs;

use App\Core\Database;
use Throwable;

final class ReportJob
{
    public function handle(array $payload, string $jobId): void
    {
        $id = (int) $payload['report_id'];
        $pdo = Database::connection();
        $query = $pdo->prepare('SELECT status FROM reports WHERE id = ?');
        $query->execute([$id]);
        $status = $query->fetchColumn();
        if ($status === 'done') {
            return;
        }
        if ($status === false) {
            throw new \RuntimeException('Заявка на отчёт не найдена');
        }
        $pdo->prepare("UPDATE reports SET status = 'processing', error = NULL WHERE id = ?")->execute([$id]);
        $dir = BASE_PATH . '/storage/reports';
        $temp = $dir . '/report-' . $id . '.tmp';
        $path = $dir . '/report-' . $id . '.csv';
        try {
            $handle = @fopen($temp, 'wb');
            if ($handle === false) {
                throw new \RuntimeException('Не удалось создать CSV');
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Заявка', 'Категория', 'Исполнитель', 'Статус', 'Приоритет', 'Создана'], ';');
            $rows = $pdo->query("SELECT t.id, t.title, c.title AS category, COALESCE(s.name, '') AS assignee,
                t.status, t.priority, t.created_at FROM tickets t
                JOIN categories c ON c.id = t.category_id LEFT JOIN staff s ON s.id = t.assignee_id ORDER BY t.id");
            while ($row = $rows->fetch()) {
                fputcsv($handle, array_values($row), ';');
            }
            fclose($handle);
            if (!rename($temp, $path)) {
                throw new \RuntimeException('Не удалось сохранить CSV');
            }
            $pdo->prepare("UPDATE reports SET status = 'done', path = ?, finished_at = NOW(), error = NULL WHERE id = ?")
                ->execute([basename($path), $id]);
        } catch (Throwable $error) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            @unlink($temp);
            $pdo->prepare("UPDATE reports SET status = 'failed', error = ?, finished_at = NOW() WHERE id = ?")
                ->execute([$error->getMessage(), $id]);
            throw $error;
        }
    }
}
