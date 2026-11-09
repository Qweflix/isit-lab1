<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Auth;
use App\Models\PgRepository;
abstract class ResourceApiController
{
    protected string $resource;
    private function repo(): PgRepository { return new PgRepository($this->resource); }
    private function find(string $id): array
    {
        $row = $this->repo()->find(record_id($id));
        if (!$row) json_error('Запись не найдена', 404);
        return $row;
    }
    public function index(): void
    {
        $query = [];
        if ($this->resource === 'tickets') {
            foreach (['q','status','category_id','sort','order'] as $key) {
                if (isset($_GET[$key])) {
                    if (!is_string($_GET[$key])) json_error('Ошибка проверки фильтра', 422, [$key => 'Требуется строка.']);
                    $query[$key] = trim($_GET[$key]);
                }
            }
            if (isset($query['status']) && !isset(config('statuses')[$query['status']])) json_error('Ошибка фильтра',422,['status'=>'Недопустимый статус.']);
            if (isset($query['category_id']) && !filter_var($query['category_id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) json_error('Ошибка фильтра',422,['category_id'=>'Нужен положительный ID.']);
            if (isset($query['sort']) && !in_array($query['sort'], ['id','title','due_date','created_at'],true)) json_error('Ошибка сортировки',422,['sort'=>'Недопустимое поле.']);
            if (isset($query['order']) && !in_array($query['order'],['asc','desc'],true)) json_error('Ошибка сортировки',422,['order'=>'Допустимы asc и desc.']);
        }
        json_response($this->repo()->all($query));
    }
    public function show(string $id): void { json_response($this->find($id)); }
    public function store(): void
    {
        $this->writePermission();
        $defaults = match($this->resource) {
            'tickets' => ['status'=>'new','priority'=>'medium','assignee_id'=>null,'due_date'=>null],
            'categories' => ['description'=>null],
            'staff' => ['role'=>'Инженер поддержки'],
        };
        $data = array_replace($defaults, json_input());
        $this->validate($data);
        $row = $this->repo()->add($data);
        header('Location: /api/' . $this->resource . '/' . $row['id']);
        json_response($row,201);
    }
    public function update(string $id): void
    {
        $this->writePermission();
        $old = $this->find($id);
        $data = array_replace($old, json_input());
        // ID из запроса не должен менять адресуемую запись.
        $data['id'] = $old['id'];
        $this->validate($data);
        $row = $this->repo()->update($old['id'], $data);
        if (!$row) json_error('Запись не найдена',404);
        json_response($row);
    }
    public function destroy(string $id): void
    {
        Auth::requireRole('manager');
        if (!$this->repo()->delete(record_id($id))) json_error('Запись не найдена',404);
        no_content();
    }
    private function writePermission(): void
    {
        if ($this->resource !== 'tickets') Auth::requireRole('manager');
    }
    private function validate(array $data): void
    {
        $errors = [];
        $required = $this->resource === 'staff' ? ['name'=>100,'email'=>255,'role'=>100] : ['title'=>$this->resource === 'tickets' ? 200 : 150];
        foreach ($required as $key=>$max) {
            $v = $data[$key] ?? null;
            $min = in_array($key,['name','title']) ? 3 : 1;
            if (!is_string($v) || mb_strlen(trim($v))<$min || mb_strlen($v)>$max) $errors[$key]="Длина от $min до $max символов.";
        }
        if ($this->resource === 'staff' && (!is_string($data['email'] ?? null) || !filter_var($data['email'],FILTER_VALIDATE_EMAIL))) $errors['email']='Некорректный email.';
        if ($this->resource === 'tickets') {
            foreach (['status'=>'statuses','priority'=>'priorities'] as $field=>$cfg) {
                if (!is_string($data[$field] ?? null) || !isset(config($cfg)[$data[$field]])) $errors[$field]='Недопустимое значение.';
            }
            if (!is_string($data['description'] ?? null) || trim($data['description'])==='') $errors['description']='Опишите проблему.';
            foreach (['category_id'=>'categories','assignee_id'=>'staff'] as $key=>$table) {
                $v=$data[$key] ?? null;
                if ($key === 'assignee_id' && $v === null) continue;
                if (!is_int($v) || $v<1 || !(new PgRepository($table))->find($v)) $errors[$key]='Укажите числовой ID существующей записи.';
            }
            $v=$data['due_date'] ?? null;
            if ($v !== null) {
                $d=is_string($v) ? \DateTimeImmutable::createFromFormat('!Y-m-d',$v) : false;
                if (!$d || $d->format('Y-m-d')!==$v) $errors['due_date']='Дата YYYY-MM-DD или null.';
            }
        }
        if ($this->resource === 'categories') {
            $v=$data['owner_id'] ?? null;
            if (!is_int($v) || $v<1 || !(new PgRepository('staff'))->find($v)) $errors['owner_id']='Укажите числовой ID владельца.';
            if (($data['description'] ?? null) !== null && !is_string($data['description'])) $errors['description']='Требуется строка или null.';
        }
        if ($errors) json_error('Ошибка проверки данных',422,$errors);
    }
}
