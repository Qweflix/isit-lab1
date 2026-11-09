<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Cache;
final class CachedTicketRepository extends TicketRepository
{
    public function page(array $query = []): array
    {
        $q = self::normalize($query);
        $key = 'tickets:list:v' . Cache::version('tickets') . ':' . hash('sha256',json_encode($q,JSON_THROW_ON_ERROR));
        return Cache::remember($key,60,fn()=>parent::page($q));
    }
    public function find(int $id): ?array
    {
        return Cache::remember('tickets:item:v'.Cache::version('tickets').':'.$id,60,fn()=>parent::find($id));
    }
}
