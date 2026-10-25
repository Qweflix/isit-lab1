<?php
declare(strict_types=1);
namespace App\Models;

final class TaskRepository
{
    private JsonStorage $storage;

    public function __construct()
    {
        $this->storage = new JsonStorage(STORAGE_PATH . '/tasks.json');
    }

    public function all(): array
    {
        return $this->storage->all();
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public function byStatus(string $status): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['status'] === $status));
    }

    public function byProject(int $id): array
    {
        return array_values(array_filter($this->all(), fn (array $row): bool => $row['project_id'] === $id));
    }

    public function add(array $row): array
    {
        return $this->storage->add($row);
    }

}
