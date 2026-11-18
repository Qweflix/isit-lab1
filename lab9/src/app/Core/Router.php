<?php
declare(strict_types=1);
namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'path' => $path, 'handler' => $handler];
    }

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, array $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        // Query-параметры не участвуют в выборе маршрута.
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            notFound();
        }
        $allowed = [];
        foreach ($this->routes as $route) {
            $names = [];
            $parts = explode('/', $route['path']);
            foreach ($parts as &$part) {
                if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $match)) {
                    $names[] = $match[1];
                    $part = '([^/]+)';
                } else {
                    $part = preg_quote($part, '~');
                }
            }
            unset($part);
            if (!preg_match('~^' . implode('/', $parts) . '$~D', $path, $matches)) {
                continue;
            }
            $allowed[] = $route['method'];
            if ($method !== $route['method']) {
                continue;
            }
            array_shift($matches);
            $parameters = array_combine($names, array_map('rawurldecode', $matches));
            [$class, $action] = $route['handler'];
            (new $class())->$action(...$parameters);
            return;
        }
        if (is_api_request() && $allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            json_error('Метод не разрешён', 405);
        }
        // Для HTML-страниц сохраняется поведение ЛР6 (404).
        notFound();
    }
}
