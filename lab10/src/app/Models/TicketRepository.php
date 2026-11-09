<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
class TicketRepository extends PgRepository
{
    public function __construct() { parent::__construct('tickets'); }
    public static function normalize(array $query): array
    {
        return [
            'q' => trim($query['q'] ?? ''), 'status' => $query['status'] ?? '',
            'category_id' => isset($query['category_id']) ? (int)$query['category_id'] : null,
            'sort' => $query['sort'] ?? 'id', 'order' => $query['order'] ?? 'asc',
            'page' => (int)($query['page'] ?? 1), 'limit' => (int)($query['limit'] ?? 100),
        ];
    }
    public function page(array $query = []): array
    {
        $q = self::normalize($query); $where = []; $params = [];
        foreach (['status','category_id'] as $field) {
            if ($q[$field] !== '' && $q[$field] !== null) { $where[] = "$field=:$field"; $params[$field] = $q[$field]; }
        }
        if ($q['q'] !== '') { $where[] = 'title ILIKE :q'; $params['q'] = '%' . $q['q'] . '%'; }
        $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $pdo = Database::connection();
        $s = $pdo->prepare('SELECT count(*) FROM tickets' . $where);
        $s->execute($params); $total = (int)$s->fetchColumn();
        $sort = in_array($q['sort'], ['id','title','due_date','created_at'], true) ? $q['sort'] : 'id';
        $order = $q['order'] === 'desc' ? 'DESC' : 'ASC';
        $limit = max(1,min(100,$q['limit'])); $offset = (max(1,$q['page'])-1)*$limit;
        $s = $pdo->prepare("SELECT * FROM tickets$where ORDER BY $sort $order NULLS LAST, id ASC LIMIT :limit OFFSET :offset");
        foreach ($params as $key=>$value) $s->bindValue($key, $value);
        $s->bindValue('limit',$limit,\PDO::PARAM_INT);$s->bindValue('offset',$offset,\PDO::PARAM_INT);
        $s->execute(); return ['items'=>$s->fetchAll(),'total'=>$total];
    }
    public function all(array $query = []): array { return $this->page($query)['items']; }
}
