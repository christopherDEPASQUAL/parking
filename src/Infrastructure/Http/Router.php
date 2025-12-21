<?php
declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\DependencyInjection\Container;

final class Router
{
    private array $routes = [];

    public function __construct(
        private readonly Container $container
    )
    {
    }

    public function addRoute(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                $controllerClass = 'App\\Presentation\\Http\\Controller\\Api\\' . $route['controller'];
                $controller = $this->container->get($controllerClass);
                $action = $route['action'];

                $controller->$action();
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found'
        ]);
    }
}
