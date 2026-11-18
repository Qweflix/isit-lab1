<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key): mixed
{
    static $settings = null;
    $settings ??= require BASE_PATH . '/config/app.php';
    return $settings[$key] ?? null;
}

function view(string $template, array $data = []): void
{
    header('Content-Type: text/html; charset=utf-8');
    extract($data, EXTR_SKIP);
    require APP_PATH . '/Views/layout/header.php';
    require APP_PATH . '/Views/' . $template . '.php';
    require APP_PATH . '/Views/layout/footer.php';
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 302);
    exit;
}

function notFound(): never
{
    if (is_api_request()) {
        json_error('Ресурс не найден', 404);
    }
    http_response_code(404);
    view('errors/404', ['title' => 'Страница не найдена']);
    exit;
}

function input(array $source, string $key): string
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
}

function is_api_request(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return is_string($path) && ($path === '/api' || str_starts_with($path, '/api/'));
}

function json_response(mixed $data, int $code = 200): never
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo $json;
    exit;
}

function json_error(string $message, int $code, array $errors = []): never
{
    json_response(['error' => $message, 'errors' => (object) $errors], $code);
}

function no_content(): never
{
    http_response_code(204);
    header_remove('Content-Type');
    exit;
}

function json_input(): array
{
    try {
        $value = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        json_error('Некорректный JSON', 400);
    }
    if (!$value instanceof stdClass) {
        json_error('Тело запроса должно быть JSON-объектом', 400);
    }
    return (array) $value;
}

function record_id(string $id): int
{
    // Не допускаем переполнение и не преобразуем нечисловые сегменты в 0.
    $value = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($value === false) {
        json_error('Ресурс не найден', 404);
    }
    return $value;
}
