<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\{Database, Cache};
final class ReportRepository
{
    public function summary(): array
    {
        $key='report:summary:t'.Cache::version('tickets').':c'.Cache::version('categories');
        return Cache::remember($key,120,fn()=>Database::connection()->query(
            "SELECT c.id AS category_id, c.title AS category_title, count(t.id)::int AS total,
            count(t.id) FILTER (WHERE t.status='new')::int AS new,
            count(t.id) FILTER (WHERE t.status='in_progress')::int AS in_progress,
            count(t.id) FILTER (WHERE t.status='done')::int AS done,
            count(t.id) FILTER (WHERE t.assignee_id IS NULL)::int AS unassigned
            FROM categories c LEFT JOIN tickets t ON t.category_id=c.id GROUP BY c.id,c.title ORDER BY c.id"
        )->fetchAll());
    }
}
