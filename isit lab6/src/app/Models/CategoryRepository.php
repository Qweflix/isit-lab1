<?php
declare(strict_types=1);
namespace App\Models;

final class CategoryRepository
{
    private JsonStorage $storage;

    public function __construct()
    {
        $this->storage = new JsonStorage(STORAGE_PATH . '/categories.json');
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
