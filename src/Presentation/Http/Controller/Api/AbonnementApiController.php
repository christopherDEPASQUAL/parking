<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Abonnements\CreateAbonnementRequest;
use App\Application\DTO\Abonnements\ListParkingAbonnementsRequest;
use App\Application\DTO\Abonnements\ListUserAbonnementsRequest;
use App\Application\UseCase\Abonnements\CreateAbonnement;
use App\Application\UseCase\Abonnements\ListParkingAbonnements;
use App\Application\UseCase\Abonnements\ListUserAbonnements;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class AbonnementApiController
{
    public function __construct(
        private readonly CreateAbonnement $createAbonnement,
        private readonly ListParkingAbonnements $listParkingAbonnements,
        private readonly ListUserAbonnements $listUserAbonnements,
        private readonly ParkingRepositoryInterface $parkingRepository
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
            $this->assertOwnerAccess($request->parkingId);
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

    private function assertOwnerAccess(string $parkingId): void
    {
        $authUserId = $_SERVER['AUTH_USER_ID'] ?? null;
        $role = $_SERVER['AUTH_USER_ROLE'] ?? null;
        if ($authUserId === null) {
            throw new \InvalidArgumentException('Missing authenticated user.');
        }
        if ($role === 'admin') {
            return;
        }
        if ($role !== 'proprietor') {
            throw new \InvalidArgumentException('Owner access required.');
        }
        $parking = $this->parkingRepository->findById(ParkingId::fromString($parkingId));
        if ($parking === null) {
            throw new \InvalidArgumentException('Parking not found.');
        }
        if ($parking->getUserId()->getValue() !== $authUserId) {
            throw new \InvalidArgumentException('Not authorized to access this parking.');
        }
    }
}
