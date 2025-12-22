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

    public function setupApiRoutes(): void
    {
        $this->addRoute('POST', '/api/auth/register', [$this->authController, 'register']);
        $this->addRoute('POST', '/api/auth/login', [$this->authController, 'login']);
        $this->addRoute('POST', '/api/auth/refresh', [$this->authController, 'refresh']);
        $this->addRoute('POST', '/api/auth/logout', [$this->authController, 'logout']);

        $this->addRoute('POST', '/api/parkings/search', [$this->parkingController, 'search']);
        $this->addRoute('GET', '/api/parkings/{parkingId}', [$this->parkingController, 'view']);
        $this->addRoute('POST', '/api/parkings', [$this->parkingController, 'create']);
        $this->addRoute('POST', '/api/parkings/enter', [$this->parkingController, 'enter']);
        $this->addRoute('POST', '/api/parkings/exit', [$this->parkingController, 'exit']);
        $this->addRoute('GET', '/api/users/{userId}/stationnements', [$this->parkingController, 'listUserStationnements']);
        $this->addRoute('GET', '/api/parkings/{parkingId}/availability', [$this->parkingController, 'availability']);

        $this->addRoute('POST', '/api/reservations', [$this->reservationController, 'create']);
        $this->addRoute('POST', '/api/reservations/cancel', [$this->reservationController, 'cancel']);
        $this->addRoute('GET', '/api/users/{userId}/reservations', [$this->reservationController, 'listUserReservations']);
        $this->addRoute('GET', '/api/reservations/{reservationId}/invoice', [$this->reservationController, 'invoice']);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->callHandler($route['handler'], $matches);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route non trouvée.'
        ]);
    }

    private function convertPathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function callHandler(callable $handler, array $params): void
    {
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            if (method_exists($controller, $method)) {
                call_user_func_array([$controller, $method], $params);
                return;
            }
        }

        call_user_func_array($handler, $params);
    }
}

