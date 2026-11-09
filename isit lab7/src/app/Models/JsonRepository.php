<?php
declare(strict_types=1);
namespace App\Models;

abstract class JsonRepository
{
    protected string $name;

    private function access(bool $write, callable $operation): mixed
    {
        $handle = @fopen(STORAGE_PATH . '/' . $this->name . '.json', $write ? 'r+' : 'r');
        if ($handle === false) {
            throw new \RuntimeException('Хранилище недоступно: ' . $this->name);
        }
        try {
            if (!flock($handle, $write ? LOCK_EX : LOCK_SH)) {
                throw new \RuntimeException('Не удалось заблокировать хранилище.');
            }
            $rows = json_decode(stream_get_contents($handle), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new \RuntimeException('Неверная структура хранилища.');
            }
            $result = $operation($rows);
            if ($write) {
                $json = json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
                rewind($handle);
                if (fwrite($handle, $json) !== strlen($json) || !ftruncate($handle, strlen($json)) || !fflush($handle)) {
                    throw new \RuntimeException('Не удалось сохранить данные.');
                }
            }
            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function all(): array
    {
        return $this->access(false, fn (array $rows): array => $rows);
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public function add(array $data): array
    {
        return $this->access(true, function (array &$rows) use ($data): array {
            $data['id'] = $rows === [] ? 1 : max(array_column($rows, 'id')) + 1;
            $rows[] = $data;
            return $data;
        });
    }

    public function update(int $id, array $data): ?array
    {
        return $this->access(true, function (array &$rows) use ($id, $data): ?array {
            foreach ($rows as &$row) {
                if ($row['id'] === $id) {
                    $row = array_replace($row, $data, ['id' => $id]);
                    return $row;
                }
            }
            return null;
        });
    }

    public function delete(int $id): bool
    {
        return $this->access(true, function (array &$rows) use ($id): bool {
            foreach ($rows as $key => $row) {
                if ($row['id'] === $id) {
                    array_splice($rows, $key, 1);
                    return true;
                }
            }
            return false;
        });
    }
}
