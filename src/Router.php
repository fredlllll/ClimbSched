<?php

declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method . ' ' . $path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $key = $method . ' ' . $path;
        if (!isset($this->routes[$key])) {
            http_response_code(404);
            echo 'Not Found';

            return;
        }

        $this->routes[$key]();
    }
}
