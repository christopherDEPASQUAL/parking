<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\UseCase\Parkings\SearchParkings;
use App\Application\UseCase\Parkings\ViewParking;
use App\Application\UseCase\Parkings\CreateParking;
use App\Application\UseCase\Parkings\EnterParking;
use App\Application\UseCase\Parkings\ExitParking;
use App\Application\UseCase\Parkings\ListUserStationnements;
use App\Application\UseCase\Parkings\GetParkingAvailability;
use App\Application\DTO\Parkings\SearchParkingsRequest;
use App\Application\DTO\Parkings\ViewParkingRequest;
use App\Application\DTO\Parkings\CreateParkingRequest;
use App\Application\DTO\Parkings\EnterParkingRequest;
use App\Application\DTO\Parkings\ExitParkingRequest;
use App\Application\DTO\Parkings\ListUserStationnementsRequest;
use App\Application\DTO\Parkings\GetParkingAvailabilityRequest;

final class ParkingApiController
{
    public function __construct(
        private readonly SearchParkings $searchParkings,
        private readonly ViewParking $viewParking,
        private readonly CreateParking $createParking,
        private readonly EnterParking $enterParking,
        private readonly ExitParking $exitParking,
        private readonly ListUserStationnements $listUserStationnements,
        private readonly GetParkingAvailability $getParkingAvailability
    ) {}

    public function search(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $request = new SearchParkingsRequest(
                $data['latitude'] ?? 0.0,
                $data['longitude'] ?? 0.0,
                $data['radiusKm'] ?? null,
                $data['at'] ?? null,
                $data['minAvailableSpots'] ?? null
            );

            $response = $this->searchParkings->execute($request);

            http_response_code(200);
            echo json_encode($response->parkings);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function view(string $parkingId): void
    {
        header('Content-Type: application/json');

        try {
            $request = new ViewParkingRequest($parkingId);
            $response = $this->viewParking->execute($request);

            http_response_code(200);
            echo json_encode([
                'id' => $response->id,
                'name' => $response->name,
                'address' => $response->address,
                'description' => $response->description,
                'latitude' => $response->latitude,
                'longitude' => $response->longitude,
                'capacity' => $response->capacity,
                'ownerId' => $response->ownerId,
                'pricingPlan' => $response->pricingPlan,
                'openingHours' => $response->openingHours,
                'createdAt' => $response->createdAt,
                'updatedAt' => $response->updatedAt
            ]);
        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function create(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new CreateParkingRequest(
                $data['name'],
                $data['address'],
                $data['capacity'],
                $data['latitude'],
                $data['longitude'],
                $data['ownerId'],
                $data['pricingTiers'] ?? [],
                $data['defaultPricePerStepCents'] ?? 0,
                $data['overstayPenaltyCents'] ?? null,
                $data['openingHours'] ?? [],
                $data['description'] ?? null
            );

            $response = $this->createParking->execute($request);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'parkingId' => $response->parkingId,
                'capacity' => $response->capacity,
                'createdAt' => $response->createdAt
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function enter(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new EnterParkingRequest(
                $data['parkingId'],
                $data['userId'],
                $data['spotId'],
                $data['entryTime'] ?? null
            );

            $response = $this->enterParking->execute($request);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'sessionId' => $response->sessionId,
                'parkingId' => $response->parkingId,
                'userId' => $response->userId,
                'spotId' => $response->spotId,
                'entryTime' => $response->entryTime
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function exit(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new ExitParkingRequest(
                $data['userId'],
                $data['parkingId'],
                $data['exitedAt'] ?? null
            );

            $response = $this->exitParking->execute($request);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'sessionId' => $response->sessionId,
                'exitedAt' => $response->exitedAt,
                'durationMinutes' => $response->durationMinutes,
                'basePriceCents' => $response->basePriceCents,
                'penaltyCents' => $response->penaltyCents,
                'totalPriceCents' => $response->totalPriceCents
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function listUserStationnements(string $userId): void
    {
        header('Content-Type: application/json');

        try {
            $request = new ListUserStationnementsRequest($userId);
            $response = $this->listUserStationnements->execute($request);

            http_response_code(200);
            echo json_encode($response->stationnements);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function availability(string $parkingId, string $at): void
    {
        header('Content-Type: application/json');

        try {
            $request = new GetParkingAvailabilityRequest($parkingId, new \DateTimeImmutable($at));
            $response = $this->getParkingAvailability->execute($request);

            http_response_code(200);
            echo json_encode([
                'parkingId' => $response->parkingId,
                'freeSpots' => $response->freeSpots,
                'totalCapacity' => $response->totalCapacity,
                'at' => $response->at->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
