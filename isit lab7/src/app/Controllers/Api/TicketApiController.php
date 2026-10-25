<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Models\TicketRepository;
use App\Models\CategoryRepository;

final class TicketApiController
{
    private TicketRepository $records;
    private CategoryRepository $categories;

    public function __construct()
    {
        $this->records = new TicketRepository();
        $this->categories = new CategoryRepository();
    }

    public function index(): void
    {
        $rows = $this->records->all();
        if (isset($_GET['status'])) {
            $status = input($_GET, 'status');
            if (!array_key_exists($status, config('statuses'))) {
                json_error('Ошибка проверки фильтра', 422, ['status' => 'Недопустимый статус.']);
            }
            $rows = array_values(array_filter($rows, fn (array $row): bool => $row['status'] === $status));
        }
        if (isset($_GET['category_id'])) {
            $id = input($_GET, 'category_id');
            if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                json_error('Ошибка проверки фильтра', 422, ['category_id' => 'Требуется положительный целый номер.']);
            }
            $rows = array_values(array_filter($rows, fn (array $row): bool => $row['category_id'] === (int) $id));
        }
        if (isset($_GET['q'])) {
            if (!is_string($_GET['q'])) {
                json_error('Ошибка проверки фильтра', 422, ['q' => 'Поиск должен быть строкой.']);
            }
            $query = trim($_GET['q']);
            $rows = array_values(array_filter($rows, fn (array $row): bool => mb_stripos($row['title'], $query, 0, 'UTF-8') !== false));
        }
        json_response($rows);
    }

    public function show(string $id): void
    {
        json_response($this->find($id));
    }

    private function find(string $id): array
    {
        $record = $this->records->find(record_id($id));
        if ($record === null) {
            json_error('Запись не найдена', 404);
        }
        return $record;
    }

    public function store(): void
    {
        $data = array_replace(['status' => 'new', 'priority' => 'medium', 'description' => ''], $this->clean(json_input()));
        $this->validate($data);
        $created = $this->records->add($data);
        header('Location: /api/tickets/' . $created['id']);
        json_response($created, 201);
    }

    public function update(string $id): void
    {
        $old = $this->find($id);
        // По условию лабораторной PUT и PATCH обновляют только присланные поля.
        $data = array_replace($old, $this->clean(json_input()));
        $this->validate($data);
        $updated = $this->records->update($old['id'], $data);
        if ($updated === null) {
            json_error('Запись не найдена', 404);
        }
        json_response($updated);
    }

    public function destroy(string $id): void
    {
        if (!$this->records->delete(record_id($id))) {
            json_error('Запись не найдена', 404);
        }
        no_content();
    }

    private function clean(array $input): array
    {
        $data = array_intersect_key($input, array_flip(['title', 'category_id', 'status', 'priority', 'description']));
        foreach ($data as &$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        }
        return $data;
    }

    private function validate(array $data): void
    {
        $errors = [];
        if (!is_string($data['title'] ?? null) || mb_strlen($data['title'], 'UTF-8') < 3) {
            $errors['title'] = 'Название должно содержать не менее 3 символов.';
        }
        if (!is_int($data['category_id'] ?? null) || $this->categories->find($data['category_id']) === null) {
            $errors['category_id'] = 'Укажите числовой ID существующей связанной записи.';
        }
        if (!is_string($data['status'] ?? null) || !array_key_exists($data['status'], config('statuses'))) {
            $errors['status'] = 'Недопустимый статус.';
        }
        if (!is_string($data['priority'] ?? null) || !array_key_exists($data['priority'], config('priorities'))) {
            $errors['priority'] = 'Недопустимый приоритет.';
        }
        if (!is_string($data['description'] ?? null) || $data['description'] === '') {
            $errors['description'] = 'Опишите проблему.';
        }
        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
    }
}
