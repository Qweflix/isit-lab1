<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
class PgRepository
{
    private string $table;
    private array $fields;
    public function __construct(string $resource)
    {
        $definitions = [
            'tickets' => ['category_id','assignee_id','title','description','status','priority','due_date'],
            'categories' => ['title','description','owner_id'],
            'staff' => ['name','email','role'],
        ];
        if (!isset($definitions[$resource])) throw new \InvalidArgumentException('Unknown resource');
        $this->table = $resource;
        $this->fields = $definitions[$resource];
    }
    public function all(array $query = []): array
    {
        $where = []; $params = [];
        foreach (['category_id','status'] as $key) {
            if (isset($query[$key])) { $where[] = "$key=:$key"; $params[$key] = $query[$key]; }
        }
        if (isset($query['q']) && $query['q'] !== '') {
            $where[] = 'title ILIKE :q';
            $params['q'] = '%' . $query['q'] . '%';
        }
        $sort = $query['sort'] ?? 'id';
        if (!in_array($sort, ['id','title','due_date','created_at'], true)) $sort = 'id';
        $direction = ($query['order'] ?? '') === 'desc' ? 'DESC' : 'ASC';
        $sql = 'SELECT * FROM ' . $this->table . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $sort $direction NULLS LAST, id ASC";
        $s = Database::connection()->prepare($sql); $s->execute($params);
        return $s->fetchAll();
    }
    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM ' . $this->table . ' WHERE id=:id');
        $s->execute(['id' => $id]); return $s->fetch() ?: null;
    }
    public function add(array $data): array
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        $keys = array_keys($data);
        $s = Database::connection()->prepare('INSERT INTO ' . $this->table . '(' . implode(',', $keys) . ') VALUES (:' . implode(',:', $keys) . ') RETURNING *');
        $s->execute($data); $row = $s->fetch();
        \App\Core\Cache::invalidate($this->table);
        return $row;
    }
    public function update(int $id, array $data): ?array
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        $set = implode(',', array_map(fn($key) => "$key=:$key", array_keys($data)));
        $s = Database::connection()->prepare('UPDATE ' . $this->table . " SET $set WHERE id=:id RETURNING *");
        $s->execute($data + ['id' => $id]); $row = $s->fetch() ?: null;
        if ($row) \App\Core\Cache::invalidate($this->table);
        return $row;
    }
    public function delete(int $id): bool
    {
        $s = Database::connection()->prepare('DELETE FROM ' . $this->table . ' WHERE id=:id');
        $s->execute(['id' => $id]); $deleted = $s->rowCount() > 0;
        if ($deleted) \App\Core\Cache::invalidate($this->table);
        return $deleted;
    }
}
