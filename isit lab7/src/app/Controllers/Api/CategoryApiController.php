<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Models\CategoryRepository;
use App\Models\TicketRepository;

final class CategoryApiController
{
    private CategoryRepository $records;

    public function __construct()
    {
        $this->records = new CategoryRepository();
    }

    public function index(): void
    {
        json_response($this->records->all());
    }

    private function find(string $id): array
    {
        $record = $this->records->find(record_id($id));
        if ($record === null) {
            json_error('Запись не найдена', 404);
        }
        return $record;
    }

    public function show(string $id): void
    {
        json_response($this->find($id));
    }

    public function store(): void
    {
        $data = array_replace(['description' => ''], $this->clean(json_input()));
        $this->validate($data);
        $created = $this->records->add($data);
        header('Location: /api/categories/' . $created['id']);
        json_response($created, 201);
    }

    public function update(string $id): void
    {
        $old = $this->find($id);
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
        $record = $this->find($id);
        if ((new TicketRepository())->byCategory($record['id']) !== []) {
            json_error('Нельзя удалить категорию, пока в ней есть заявки', 409);
        }
        if (!$this->records->delete($record['id'])) {
            json_error('Запись не найдена', 404);
        }
        no_content();
    }
    public function tickets(string $id): void
    {
        $record = $this->find($id);
        json_response((new TicketRepository())->byCategory($record['id']));
    }

    private function clean(array $input): array
    {
        $data = array_intersect_key($input, array_flip(['title', 'description']));
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
        if (!is_string($data['description'] ?? null)) {
            $errors['description'] = 'Описание должно быть строкой.';
        }
        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
    }
}
