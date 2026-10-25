<?php
declare(strict_types=1);
namespace App\Models;

final class TaskRepository extends JsonRepository
{
    protected string $name = 'tasks';

    public function byStatus(string $status): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['status'] === $status));
    }

    public function byProject(int $id): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['project_id'] === $id));
    }
}
