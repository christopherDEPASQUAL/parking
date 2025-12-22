<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

final class HealthController
{
    public function ping(): void
    {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}
