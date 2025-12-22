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
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\OpeningSchedule;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;

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
        private readonly ListOverstayedDrivers $listOverstayedDrivers,
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();
            $ownerId = $data['owner_id'] ?? ($_SERVER['AUTH_USER_ID'] ?? null);
            if ($ownerId === null) {
                throw new \InvalidArgumentException('owner_id is required');
            }

            $pricingPlan = is_array($data['pricing_plan'] ?? null) ? $data['pricing_plan'] : [];
            $pricingTiers = $data['pricing_tiers'] ?? ($pricingPlan['tiers'] ?? []);
            $defaultPrice = $data['default_price_per_step_cents'] ?? ($pricingPlan['defaultPricePerStepCents'] ?? 0);
            $overstayPenalty = $data['overstay_penalty_cents'] ?? ($pricingPlan['overstayPenaltyCents'] ?? null);
            $subscriptionPrices = $data['subscription_prices'] ?? ($pricingPlan['subscriptionPrices'] ?? []);
            $openingHours = $data['opening_hours'] ?? ($data['opening_schedule'] ?? []);

            $request = new CreateParkingRequest(
                $ownerId,
                $data['name'] ?? throw new \InvalidArgumentException('name is required'),
                $data['address'] ?? throw new \InvalidArgumentException('address is required'),
                (int) ($data['capacity'] ?? throw new \InvalidArgumentException('capacity is required')),
                (float) ($data['latitude'] ?? throw new \InvalidArgumentException('latitude is required')),
                (float) ($data['longitude'] ?? throw new \InvalidArgumentException('longitude is required')),
                $pricingTiers,
                (int) $defaultPrice,
                isset($overstayPenalty) ? (int) $overstayPenalty : null,
                $subscriptionPrices,
                $openingHours,
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
            $normalized = [];
            foreach ($items as $item) {
                $payload = $this->normalizeParkingPayload($item);
                if (array_key_exists('free_spots', $item)) {
                    $payload['free_spots'] = $item['free_spots'];
                }
                $normalized[] = $payload;
            }
            $this->jsonResponse(['success' => true, 'items' => $normalized]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function details(): void
    {
        try {
            $request = GetParkingDetailsRequest::fromArray($_GET);
            $details = $this->getParkingDetails->execute($request);
            $this->jsonResponse(['success' => true, 'parking' => $this->normalizeParkingPayload($details)]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function availability(): void
    {
        try {
            $parkingId = $_GET['parking_id'] ?? ($_GET['id'] ?? null);
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $atValue = $_GET['at'] ?? $_GET['starts_at'] ?? null;
            $at = $atValue ? new \DateTimeImmutable($atValue) : new \DateTimeImmutable();

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
            $parkingId = $data['parking_id'] ?? ($_GET['id'] ?? null);
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $request = new UpdateParkingOpeningHoursRequest(
                $parkingId,
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
            $payload = $_GET;
            if (!isset($payload['parking_id']) && isset($payload['id'])) {
                $payload['parking_id'] = $payload['id'];
            }
            if (!isset($payload['year'], $payload['month']) && isset($payload['month'])) {
                [$year, $month] = explode('-', (string) $payload['month']) + [null, null];
                if ($year !== null && $month !== null) {
                    $payload['year'] = $year;
                    $payload['month'] = $month;
                }
            }
            $request = GetParkingMonthlyRevenueRequest::fromArray($payload);
            $data = $this->getParkingMonthlyRevenue->execute($request);
            $this->jsonResponse(['success' => true] + $data);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function overstayedDrivers(): void
    {
        try {
            $payload = $_GET;
            if (!isset($payload['parking_id']) && isset($payload['id'])) {
                $payload['parking_id'] = $payload['id'];
            }
            if (!isset($payload['at']) && isset($payload['month'])) {
                $payload['at'] = $payload['month'] . '-01';
            }
            $request = ListOverstayedDriversRequest::fromArray($payload);
            $items = $this->listOverstayedDrivers->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function detailsById(): void
    {
        try {
            $parkingId = $_GET['id'] ?? ($_GET['parking_id'] ?? null);
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $request = GetParkingDetailsRequest::fromArray(['parking_id' => $parkingId]);
            $details = $this->getParkingDetails->execute($request);
            $this->jsonResponse($this->normalizeParkingPayload($details));
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function listOwner(): void
    {
        try {
            $ownerId = $_SERVER['AUTH_USER_ID'] ?? null;
            if ($ownerId === null) {
                throw new \InvalidArgumentException('Missing authenticated user.');
            }

            $parkings = $this->parkingRepository->findByOwnerId(UserId::fromString($ownerId));
            $items = [];
            foreach ($parkings as $parking) {
                $items[] = $this->serializeParkingEntity($parking);
            }
            $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateDetails(): void
    {
        try {
            $parkingId = $_GET['id'] ?? null;
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }

            $data = $this->readJson();
            $parking = $this->parkingRepository->findById(ParkingId::fromString($parkingId));
            if ($parking === null) {
                throw new \InvalidArgumentException('Parking not found.');
            }
            $authUserId = $_SERVER['AUTH_USER_ID'] ?? null;
            $role = $_SERVER['AUTH_USER_ROLE'] ?? null;
            if ($authUserId !== null && $parking->getUserId()->getValue() !== $authUserId && $role !== 'admin') {
                throw new \InvalidArgumentException('Not authorized to update this parking.');
            }

            $name = $data['name'] ?? $parking->getName();
            $address = $data['address'] ?? $parking->getAddress();
            $description = array_key_exists('description', $data) ? $data['description'] : $parking->getDescription();
            $capacity = isset($data['capacity']) ? (int) $data['capacity'] : $parking->getTotalCapacity();
            $latitude = isset($data['latitude']) ? (float) $data['latitude'] : $parking->getLocation()->getLatitude();
            $longitude = isset($data['longitude']) ? (float) $data['longitude'] : $parking->getLocation()->getLongitude();

            $openingHours = $data['opening_hours'] ?? ($data['opening_schedule'] ?? null);
            $openingSchedule = $openingHours !== null
                ? ($openingHours === [] ? OpeningSchedule::alwaysOpen() : new OpeningSchedule($openingHours))
                : $parking->getOpeningSchedule();

            $updated = new \App\Domain\Entity\Parking(
                $parking->getId(),
                $name,
                $address,
                $capacity,
                $parking->getPricingPlan(),
                new GeoLocation($latitude, $longitude),
                $openingSchedule,
                $parking->getUserId(),
                $description,
                $parking->getCreatedAt(),
                new \DateTimeImmutable()
            );

            $this->parkingRepository->save($updated);

            $this->jsonResponse(['success' => true, 'parking' => $this->serializeParkingEntity($updated)]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getOpeningHours(): void
    {
        try {
            $parkingId = $_GET['id'] ?? null;
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $parking = $this->parkingRepository->findById(ParkingId::fromString($parkingId));
            if ($parking === null) {
                throw new \InvalidArgumentException('Parking not found.');
            }
            $this->jsonResponse([
                'parking_id' => $parking->getId()->getValue(),
                'opening_hours' => $parking->getOpeningSchedule()->toArray(),
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getPricingPlan(): void
    {
        try {
            $parkingId = $_GET['id'] ?? null;
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $parking = $this->parkingRepository->findById(ParkingId::fromString($parkingId));
            if ($parking === null) {
                throw new \InvalidArgumentException('Parking not found.');
            }
            $this->jsonResponse($parking->getPricingPlan()->toArray());
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updatePricingPlan(): void
    {
        try {
            $parkingId = $_GET['id'] ?? null;
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $data = $this->readJson();
            $pricingPlan = is_array($data) ? $data : [];

            $request = new UpdateParkingTariffRequest(
                $parkingId,
                $pricingPlan['tiers'] ?? ($data['pricing_tiers'] ?? []),
                (int) ($pricingPlan['defaultPricePerStepCents'] ?? $data['default_price_per_step_cents'] ?? 0),
                isset($pricingPlan['overstayPenaltyCents']) ? (int) $pricingPlan['overstayPenaltyCents'] : ($data['overstay_penalty_cents'] ?? null),
                $pricingPlan['subscriptionPrices'] ?? ($data['subscription_prices'] ?? [])
            );

            $this->updateParkingTariff->execute($request);
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeParkingPayload(array $payload): array
    {
        $location = $payload['location'] ?? null;
        $latitude = $payload['latitude']
            ?? ($location['latitude'] ?? ($location['lat'] ?? null));
        $longitude = $payload['longitude']
            ?? ($location['longitude'] ?? ($location['lng'] ?? null));
        $normalized = [
            'id' => $payload['id'] ?? $payload['parking_id'] ?? null,
            'name' => $payload['name'] ?? '',
            'address' => $payload['address'] ?? '',
        ];

        if (isset($payload['owner_id'])) {
            $normalized['owner_id'] = $payload['owner_id'];
        }
        if (array_key_exists('description', $payload) && $payload['description'] !== null) {
            $normalized['description'] = $payload['description'];
        }
        if ($latitude !== null) {
            $normalized['latitude'] = $latitude;
        }
        if ($longitude !== null) {
            $normalized['longitude'] = $longitude;
        }
        if (isset($payload['capacity']) || isset($payload['total_capacity'])) {
            $normalized['capacity'] = $payload['capacity'] ?? $payload['total_capacity'];
        }
        if (isset($payload['opening_schedule'])) {
            $normalized['opening_schedule'] = $payload['opening_schedule'];
        }
        if (isset($payload['pricing_plan'])) {
            $normalized['pricing_plan'] = $payload['pricing_plan'];
        }
        if (isset($payload['created_at'])) {
            $normalized['created_at'] = $payload['created_at'];
        }
        if (isset($payload['updated_at'])) {
            $normalized['updated_at'] = $payload['updated_at'];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParkingEntity(\App\Domain\Entity\Parking $parking): array
    {
        return [
            'id' => $parking->getId()->getValue(),
            'owner_id' => $parking->getUserId()->getValue(),
            'name' => $parking->getName(),
            'address' => $parking->getAddress(),
            'description' => $parking->getDescription(),
            'latitude' => $parking->getLocation()->getLatitude(),
            'longitude' => $parking->getLocation()->getLongitude(),
            'capacity' => $parking->getTotalCapacity(),
            'opening_schedule' => $parking->getOpeningSchedule()->toArray(),
            'pricing_plan' => $parking->getPricingPlan()->toArray(),
            'created_at' => $parking->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $parking->getUpdatedAt()->format(DATE_ATOM),
        ];
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
