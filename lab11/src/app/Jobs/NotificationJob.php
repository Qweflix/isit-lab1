<?php
declare(strict_types=1);
namespace App\Jobs;

use App\Core\Database;

final class NotificationJob
{
    public function handle(array $payload, string $jobId): void
    {
        $pdo = Database::connection();
        $ticket = $pdo->prepare('SELECT title FROM tickets WHERE id = ?');
        $ticket->execute([(int) $payload['ticket_id']]);
        $title = $ticket->fetchColumn();
        if ($title === false) {
            throw new \RuntimeException('Заявка не найдена');
        }
        $body = 'Статус заявки «' . $title . '» изменён на ' . $payload['status'];
        $query = $pdo->prepare('INSERT INTO notifications (job_id, staff_id, ticket_id, body)
            SELECT ?, s.id, ?, ? FROM staff s
            WHERE s.id <> ? AND EXISTS (SELECT 1 FROM categories c WHERE c.owner_id = s.id)
            ON CONFLICT (job_id, staff_id) DO NOTHING');
        $query->execute([$jobId, (int) $payload['ticket_id'], $body, (int) $payload['actor_id']]);
    }
}
