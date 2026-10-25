<?php
declare(strict_types=1);
namespace App\Models;

final class ProjectRepository
{
    private JsonStorage $storage;

    public function __construct()
    {
        $this->storage = new JsonStorage(STORAGE_PATH . '/projects.json');
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

}
