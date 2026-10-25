<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Models\UserRepository;


final class UserApiController
{
    private UserRepository $records;

    public function __construct()
    {
        $this->records = new UserRepository();
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
        $data = array_replace(['role' => 'Инженер' ], $this->clean(json_input()));
        $this->validate($data);
        $created = $this->records->add($data);
        header('Location: /api/users/' . $created['id']);
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
        
        if (!$this->records->delete($record['id'])) {
            json_error('Запись не найдена', 404);
        }
        no_content();
    }
    
    private function clean(array $input): array
    {
        $data = array_intersect_key($input, array_flip(['name', 'email', 'role']));
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
        if (!is_string($data['name'] ?? null) || mb_strlen($data['name'], 'UTF-8') < 3) {
            $errors['name'] = 'Имя должно содержать не менее 3 символов.';
        }
        if (!is_string($data['email'] ?? null) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Укажите корректный email.';
        }
        if (!is_string($data['role'] ?? null) || $data['role'] === '') {
            $errors['role'] = 'Укажите должность.';
        }
        if ($errors !== []) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
    }
}
