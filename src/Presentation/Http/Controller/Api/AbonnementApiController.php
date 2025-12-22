<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Abonnements\CreateAbonnementRequest;
use App\Application\DTO\Abonnements\ListParkingAbonnementsRequest;
use App\Application\DTO\Abonnements\ListUserAbonnementsRequest;
use App\Application\UseCase\Abonnements\CreateAbonnement;
use App\Application\UseCase\Abonnements\ListParkingAbonnements;
use App\Application\UseCase\Abonnements\ListUserAbonnements;

final class AbonnementApiController
{
    public function __construct(
        private readonly CreateAbonnement $createAbonnement,
        private readonly ListParkingAbonnements $listParkingAbonnements,
        private readonly ListUserAbonnements $listUserAbonnements
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();
            if (!isset($data['user_id']) && isset($_SERVER['AUTH_USER_ID'])) {
                $data['user_id'] = $_SERVER['AUTH_USER_ID'];
            }
            $request = CreateAbonnementRequest::fromArray($data);
            $result = $this->createAbonnement->execute($request);
            $this->jsonResponse(['success' => true] + $result, 201);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByParking(): void
    {
        try {
            $request = ListParkingAbonnementsRequest::fromArray($_GET);
            $items = $this->listParkingAbonnements->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByUser(): void
    {
        try {
            $request = ListUserAbonnementsRequest::fromArray($_GET);
            $items = $this->listUserAbonnements->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    private function readJson(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = $raw ? json_decode($raw, true) : null;

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function errorResponse(string $message, int $status): void
    {
        $this->jsonResponse(['success' => false, 'message' => $message], $status);
    }
}
