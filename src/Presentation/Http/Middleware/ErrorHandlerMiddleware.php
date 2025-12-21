<?php declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

final class ErrorHandlerMiddleware
{
    public function handle(callable $next): void
    {
        try {
            $next();
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error.',
            ]);
        }
    }
}
