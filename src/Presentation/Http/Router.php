<?php declare(strict_types=1);

namespace App\Presentation\Http;

use App\Presentation\Http\Controller\Api\AuthApiController;
use App\Presentation\Http\Controller\Api\ParkingApiController;
use App\Presentation\Http\Controller\Api\ReservationApiController;

final class Router
{
    private array $routes = [];

    public function __construct(
        private readonly AuthApiController $authController,
        private readonly ParkingApiController $parkingController,
        private readonly ReservationApiController $reservationController
    ) {}

    public function register(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = $this->parsePath($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path, $params)) {
                call_user_func($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Route not found']);
    }

    public function setupApiRoutes(): void
    {
        $this->register('POST', '/api/auth/register', fn() => $this->authController->register());
        $this->register('POST', '/api/auth/login', fn() => $this->authController->login());
        $this->register('POST', '/api/auth/refresh', fn() => $this->authController->refresh());
        $this->register('POST', '/api/auth/logout', fn() => $this->authController->logout());

        $this->register('POST', '/api/parkings/search', fn() => $this->parkingController->search());
        $this->register('GET', '/api/parkings/{id}', fn($p) => $this->parkingController->view($p['id']));
        $this->register('POST', '/api/parkings', fn() => $this->parkingController->create());
        $this->register('POST', '/api/parkings/enter', fn() => $this->parkingController->enter());
        $this->register('POST', '/api/parkings/exit', fn() => $this->parkingController->exit());
        $this->register('GET', '/api/parkings/availability/{id}/{at}', fn($p) => $this->parkingController->availability($p['id'], $p['at']));
        $this->register('GET', '/api/users/{userId}/stationnements', fn($p) => $this->parkingController->listUserStationnements($p['userId']));

        $this->register('POST', '/api/reservations', fn() => $this->reservationController->create());
        $this->register('POST', '/api/reservations/cancel', fn() => $this->reservationController->cancel());
        $this->register('GET', '/api/users/{userId}/reservations', fn($p) => $this->reservationController->listUserReservations($p['userId']));
        $this->register('GET', '/api/reservations/{id}/invoice/{userId}', fn($p) => $this->reservationController->invoice($p['id'], $p['userId']));
    }

    private function parsePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return rtrim($path, '/') ?: '/';
    }

    private function matchPath(string $pattern, string $path, array &$params): bool
    {
        $params = [];
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return false;
        }

        foreach ($patternParts as $i => $part) {
            if (preg_match('/^{(\w+)}$/', $part, $matches)) {
                $params[$matches[1]] = $pathParts[$i];
            } elseif ($part !== $pathParts[$i]) {
                return false;
            }
        }

        return true;
    }
}

