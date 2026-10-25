<?php
declare(strict_types=1);
namespace App\Models;

final class TicketRepository extends JsonRepository
{
    protected string $name = 'tickets';

    public function byStatus(string $status): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['status'] === $status));
    }

    public function byCategory(int $id): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['category_id'] === $id));
    }
}
