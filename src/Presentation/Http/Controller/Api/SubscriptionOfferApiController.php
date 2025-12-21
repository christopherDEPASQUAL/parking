<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\SubscriptionOffers\CreateSubscriptionOfferRequest;
use App\Application\DTO\SubscriptionOffers\ListParkingSubscriptionOffersRequest;
use App\Application\UseCase\SubscriptionOffers\CreateSubscriptionOffer;
use App\Application\UseCase\SubscriptionOffers\ListParkingSubscriptionOffers;

final class SubscriptionOfferApiController
{
    public function __construct(
        private readonly CreateSubscriptionOffer $createSubscriptionOffer,
        private readonly ListParkingSubscriptionOffers $listParkingSubscriptionOffers
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();
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
            $request = ListParkingSubscriptionOffersRequest::fromArray($_GET);
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
}
