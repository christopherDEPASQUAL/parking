<?php declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

final class ContentTypeJsonMiddleware
{
    public function handle(callable $next): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $body = file_get_contents('php://input') ?: '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && $body !== '') {
            if (stripos($contentType, 'application/json') === false) {
                http_response_code(415);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Content-Type must be application/json.',
                ]);
                return;
            }
        }

        $next();
    }
}
