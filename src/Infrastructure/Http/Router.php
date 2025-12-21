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

    /**
     * @param array<int, string> $middlewares List of middleware FQCNs.
     */
    public function addRoute(string $method, string $path, string $controller, string $action, array $middlewares = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                $controllerClass = $route['controller'];
                if (!str_contains($controllerClass, '\\')) {
                    $controllerClass = 'App\\Presentation\\Http\\Controller\\Api\\' . $controllerClass;
                }
                $controller = $this->container->get($controllerClass);
                $action = $route['action'];

                $handler = function () use ($controller, $action): void {
                    $controller->$action();
                };

                $middlewares = $route['middlewares'] ?? [];
                foreach (array_reverse($middlewares) as $middlewareClass) {
                    $middleware = $this->container->get($middlewareClass);
                    $next = $handler;
                    $handler = function () use ($middleware, $next): void {
                        $middleware->handle($next);
                    };
                }

                $handler();
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
