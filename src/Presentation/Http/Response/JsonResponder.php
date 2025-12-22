<?php declare(strict_types=1);

namespace App\Presentation\Http\Response;

final class JsonResponder
{
    public function respond(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
