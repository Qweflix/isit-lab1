<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

final class CategoryRepository extends PgRepository
{
    protected string $table = 'categories';
    protected array $fields = ['title', 'description', 'owner_id'];

    public function add(array $data): array
    {
        $data['owner_id'] ??= (int) Database::connection()->query('SELECT id FROM staff ORDER BY id LIMIT 1')->fetchColumn();
        return parent::add($data);
    }
}
