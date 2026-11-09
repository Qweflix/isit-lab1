<?php
declare(strict_types=1);
namespace App\Models;

final class JsonStorage
{
    public function __construct(private string $path) {}

    public function all(): array
    {
        $handle = fopen($this->path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Не удалось открыть хранилище.');
        }
        try {
            if (!flock($handle, LOCK_SH)) {
                throw new \RuntimeException('Не удалось заблокировать хранилище.');
            }
            return json_decode(stream_get_contents($handle), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function add(array $record): array
    {
        $handle = fopen($this->path, 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Хранилище недоступно для записи.');
        }
        try {
            // Чтение, вычисление id и запись выполняются под одной блокировкой.
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Не удалось заблокировать хранилище.');
            }
            $rows = json_decode(stream_get_contents($handle), true, 512, JSON_THROW_ON_ERROR);
            $record['id'] = $rows === [] ? 1 : max(array_column($rows, 'id')) + 1;
            $rows[] = $record;
            $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
            rewind($handle);
            if (fwrite($handle, $json) !== strlen($json) || !ftruncate($handle, strlen($json)) || !fflush($handle)) {
                throw new \RuntimeException('Не удалось сохранить запись.');
            }
            return $record;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
