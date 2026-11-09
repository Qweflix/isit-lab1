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
    http_response_code(404);
    view('errors/404', ['title' => 'Страница не найдена']);
    exit;
}

function input(array $source, string $key): string
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
}
