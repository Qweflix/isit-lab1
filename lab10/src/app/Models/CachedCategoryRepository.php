<?php
namespace App\Models;
use App\Core\Cache;
final class CachedCategoryRepository extends CategoryRepository
{
    public function all(array $query = []): array
    {
        return Cache::remember('categories:all:v'.Cache::version('categories'),300,fn()=>parent::all());
    }
}
