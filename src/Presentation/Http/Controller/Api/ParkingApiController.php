<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Parkings\CreateParkingRequest;
use App\Application\DTO\Parkings\GetParkingAvailabilityRequest;
use App\Application\DTO\Parkings\GetParkingDetailsRequest;
use App\Application\DTO\Parkings\GetParkingMonthlyRevenueRequest;
use App\Application\DTO\Parkings\ListOverstayedDriversRequest;
use App\Application\DTO\Parkings\SearchParkingsRequest;
use App\Application\DTO\Parkings\UpdateParkingCapacityRequest;
use App\Application\DTO\Parkings\UpdateParkingOpeningHoursRequest;
use App\Application\DTO\Parkings\UpdateParkingTariffRequest;
use App\Application\UseCase\Parkings\CreateParking;
use App\Application\UseCase\Parkings\GetParkingAvailability;
use App\Application\UseCase\Parkings\GetParkingDetails;
use App\Application\UseCase\Parkings\GetParkingMonthlyRevenue;
use App\Application\UseCase\Parkings\ListOverstayedDrivers;
use App\Application\UseCase\Parkings\SearchParkings;
use App\Application\UseCase\Parkings\UpdateParkingCapacity;
use App\Application\UseCase\Parkings\UpdateParkingOpeningHours;
use App\Application\UseCase\Parkings\UpdateParkingTariff;

final class ParkingApiController
{
    public function __construct(
        private readonly CreateParking $createParking,
        private readonly SearchParkings $searchParkings,
        private readonly GetParkingDetails $getParkingDetails,
        private readonly GetParkingAvailability $getParkingAvailability,
        private readonly UpdateParkingCapacity $updateParkingCapacity,
        private readonly UpdateParkingTariff $updateParkingTariff,
        private readonly UpdateParkingOpeningHours $updateParkingOpeningHours,
        private readonly GetParkingMonthlyRevenue $getParkingMonthlyRevenue,
        private readonly ListOverstayedDrivers $listOverstayedDrivers
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();

            $request = new CreateParkingRequest(
                $data['owner_id'] ?? throw new \InvalidArgumentException('owner_id is required'),
                $data['name'] ?? throw new \InvalidArgumentException('name is required'),
                $data['address'] ?? throw new \InvalidArgumentException('address is required'),
                (int) ($data['capacity'] ?? throw new \InvalidArgumentException('capacity is required')),
                (float) ($data['latitude'] ?? throw new \InvalidArgumentException('latitude is required')),
                (float) ($data['longitude'] ?? throw new \InvalidArgumentException('longitude is required')),
                $data['pricing_tiers'] ?? [],
                (int) ($data['default_price_per_step_cents'] ?? 0),
                isset($data['overstay_penalty_cents']) ? (int) $data['overstay_penalty_cents'] : null,
                $data['subscription_prices'] ?? [],
                $data['opening_hours'] ?? [],
                $data['description'] ?? null
            );

            $response = $this->createParking->execute($request);

            $this->jsonResponse([
                'success' => true,
                'parking_id' => $response->parkingId,
                'capacity' => $response->totalCapacity,
                'created_at' => $response->createdAt->format(DATE_ATOM),
            ], 201);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function search(): void
    {
        try {
            $request = SearchParkingsRequest::fromArray($_GET);
            $items = $this->searchParkings->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function details(): void
    {
        try {
            $request = GetParkingDetailsRequest::fromArray($_GET);
            $details = $this->getParkingDetails->execute($request);
            $this->jsonResponse(['success' => true, 'parking' => $details]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function availability(): void
    {
        try {
            $parkingId = $_GET['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required');
            $at = isset($_GET['at']) ? new \DateTimeImmutable($_GET['at']) : new \DateTimeImmutable();

            $request = new GetParkingAvailabilityRequest($parkingId, $at);
            $response = $this->getParkingAvailability->execute($request);

            $this->jsonResponse([
                'success' => true,
                'parking_id' => $response->parkingId,
                'at' => $response->at->format(DATE_ATOM),
                'free_spots' => $response->freeSpots,
                'capacity' => $response->totalCapacity,
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateCapacity(): void
    {
        try {
            $data = $this->readJson();
            $request = new UpdateParkingCapacityRequest(
                $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
                (int) ($data['capacity'] ?? throw new \InvalidArgumentException('capacity is required'))
            );

            $this->updateParkingCapacity->execute($request);
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateTariff(): void
    {
        try {
            $data = $this->readJson();
            $request = new UpdateParkingTariffRequest(
                $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
                $data['pricing_tiers'] ?? [],
                (int) ($data['default_price_per_step_cents'] ?? throw new \InvalidArgumentException('default_price_per_step_cents is required')),
                isset($data['overstay_penalty_cents']) ? (int) $data['overstay_penalty_cents'] : null,
                $data['subscription_prices'] ?? []
            );

            $this->updateParkingTariff->execute($request);
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateOpeningHours(): void
    {
        try {
            $data = $this->readJson();
            $request = new UpdateParkingOpeningHoursRequest(
                $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
                $data['opening_hours'] ?? throw new \InvalidArgumentException('opening_hours is required')
            );

            $this->updateParkingOpeningHours->execute($request);
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function monthlyRevenue(): void
    {
        try {
            $request = GetParkingMonthlyRevenueRequest::fromArray($_GET);
            $data = $this->getParkingMonthlyRevenue->execute($request);
            $this->jsonResponse(['success' => true] + $data);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function overstayedDrivers(): void
    {
        try {
            $request = ListOverstayedDriversRequest::fromArray($_GET);
            $items = $this->listOverstayedDrivers->execute($request);
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
