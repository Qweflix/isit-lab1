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

    public function dispatch(string $method, string $uri): void
    {
        // Query-параметры не участвуют в выборе маршрута.
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            notFound();
        }
        foreach ($this->routes as $route) {
            if ($method !== $route['method']) {
                continue;
            }
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
            array_shift($matches);
            $parameters = array_combine($names, array_map('rawurldecode', $matches));
            [$class, $action] = $route['handler'];
            (new $class())->$action(...$parameters);
            return;
        }
        // Метод тоже является частью маршрута: неподдерживаемый POST даёт 404.
        notFound();
    }
}
