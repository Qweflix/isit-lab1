<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

abstract class PgRepository
{
    protected string $table;
    protected array $fields;

    private function normalize(array $row): array
    {
        foreach (['id', 'category_id', 'assignee_id', 'owner_id'] as $key) {
            if (isset($row[$key])) {
                $row[$key] = (int) $row[$key];
            }
        }
        unset($row['password_hash']);
        return $row;
    }

    public function all(): array
    {
        $rows = Database::connection()->query("SELECT * FROM {$this->table} ORDER BY id")->fetchAll();
        return array_map($this->normalize(...), $rows);
    }

    public function find(int $id): ?array
    {
        $query = Database::connection()->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $query->execute([$id]);
        $row = $query->fetch();
        return $row ? $this->normalize($row) : null;
    }

    public function add(array $data): array
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        $fields = array_keys($data);
        $names = implode(', ', $fields);
        $marks = implode(', ', array_fill(0, count($fields), '?'));
        $query = Database::connection()->prepare("INSERT INTO {$this->table} ($names) VALUES ($marks) RETURNING *");
        $query->execute(array_values($data));
        return $this->normalize($query->fetch());
    }

    public function update(int $id, array $data): ?array
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        if ($data === []) {
            return $this->find($id);
        }
        $assignments = implode(', ', array_map(fn (string $field): string => "$field = ?", array_keys($data)));
        $query = Database::connection()->prepare("UPDATE {$this->table} SET $assignments WHERE id = ? RETURNING *");
        $query->execute([...array_values($data), $id]);
        $row = $query->fetch();
        return $row ? $this->normalize($row) : null;
    }

    public function delete(int $id): bool
    {
        $query = Database::connection()->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $query->execute([$id]);
        return $query->rowCount() > 0;
    }
}
