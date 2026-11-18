<?php
declare(strict_types=1);
namespace App\Models;

final class StaffRepository extends PgRepository
{
    protected string $table = 'staff';
    protected array $fields = ['name', 'email', 'role', 'password_hash'];

    public function add(array $data): array
    {
        $data['password_hash'] = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
        return parent::add($data);
    }
}
