<?php
declare(strict_types=1);
namespace App\Models;

final class UserRepository extends JsonRepository
{
    protected string $name = 'users';
}
