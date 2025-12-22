<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\SubscriptionOffers\CreateSubscriptionOfferRequest;
use App\Application\DTO\SubscriptionOffers\ListParkingSubscriptionOffersRequest;
use App\Application\UseCase\SubscriptionOffers\CreateSubscriptionOffer;
use App\Application\UseCase\SubscriptionOffers\ListParkingSubscriptionOffers;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class SubscriptionOfferApiController
{
    public function __construct(
        private readonly CreateSubscriptionOffer $createSubscriptionOffer,
        private readonly ListParkingSubscriptionOffers $listParkingSubscriptionOffers,
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();
            if (!isset($data['parking_id']) && isset($_GET['id'])) {
                $data['parking_id'] = $_GET['id'];
            }
            if (isset($data['parking_id'])) {
                $this->assertOwnerAccess((string) $data['parking_id']);
            }
            $request = CreateSubscriptionOfferRequest::fromArray($data);
            $result = $this->createSubscriptionOffer->execute($request);
            $this->jsonResponse(['success' => true] + $result, 201);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByParking(): void
    {
        try {
            $payload = $_GET;
            if (!isset($payload['parking_id']) && isset($payload['id'])) {
                $payload['parking_id'] = $payload['id'];
            }
            $request = ListParkingSubscriptionOffersRequest::fromArray($payload);
            $items = $this->listParkingSubscriptionOffers->execute($request);
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
