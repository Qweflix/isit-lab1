<?php
declare(strict_types=1);
namespace App\Models;

final class TicketRepository extends PgRepository
{
    protected string $table = 'tickets';
    protected array $fields = ['category_id', 'assignee_id', 'title', 'description', 'status', 'priority', 'due_date'];

    public function byStatus(string $status): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['status'] === $status));
    }

    public function byCategory(int $id): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['category_id'] === $id));
    }
}
