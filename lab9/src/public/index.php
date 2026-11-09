<?php
declare(strict_types=1);
// В режиме встроенного сервера отдаём только файлы из public/client.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (PHP_SAPI === 'cli-server' && is_string($path) && str_starts_with($path, '/client/')) {
    $file = realpath(__DIR__ . rawurldecode($path));
    if ($file && str_starts_with($file, __DIR__ . '/client/') && is_file($file)) return false;
    if ($path === '/client/') { readfile(__DIR__.'/client/index.html'); return; }
}
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
try {
    // Все API защищены; открыт только точный маршрут входа.
    if (is_api_request() && !($path === '/api/auth/login' && $_SERVER['REQUEST_METHOD'] === 'POST')) \App\Core\Auth::user();
    $router = new \App\Core\Router();
    require BASE_PATH . '/routes/web.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (PDOException $error) {
    error_log('Database error SQLSTATE=' . $error->getCode());
    $state = (string)$error->getCode();
    if (in_array($state,['23503','23505'],true)) json_error('Конфликт связанных или уникальных данных',409);
    if (str_starts_with($state,'08') || $state === '57P01' || $state === 'HY000') json_error('База данных недоступна',503);
    json_error('Ошибка сервера, попробуйте позже',500);
} catch (Throwable $error) {
    error_log(get_class($error) . ': ' . $error->getMessage());
    json_error('Ошибка сервера, попробуйте позже',500);
}
