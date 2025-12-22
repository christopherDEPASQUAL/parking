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
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);
            if ($params === null) {
                continue;
            }

            foreach ($params as $key => $value) {
                if (!array_key_exists($key, $_GET)) {
                    $_GET[$key] = $value;
                }
            }

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

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found'
        ]);
    }

    /**
     * @return array<string, string>|null
     */
    private function matchRoute(string $pattern, string $uri): ?array
    {
        if (!str_contains($pattern, '{')) {
            return $pattern === $uri ? [] : null;
        }

        $regex = preg_replace('~\{([a-zA-Z_][a-zA-Z0-9_]*)\}~', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
