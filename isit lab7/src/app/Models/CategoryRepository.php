<?php
declare(strict_types=1);
namespace App\Models;

final class CategoryRepository extends JsonRepository
{
    protected string $name = 'categories';
}
