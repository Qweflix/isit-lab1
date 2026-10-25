<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

try {
    $router = new App\Core\Router();
    require BASE_PATH . '/routes/web.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (is_api_request()) {
        json_error('Внутренняя ошибка сервера', 500);
    }
    http_response_code(500);
    view('errors/500', ['title' => 'Ошибка сервера']);
}
