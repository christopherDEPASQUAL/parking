<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\SearchParkingsRequest;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GeoLocation;
use App\Domain\ValueObject\ParkingSearchQuery;
use App\Domain\ValueObject\UserId;

final class SearchParkings
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(SearchParkingsRequest $request): array
    {
        $ownerId = $request->ownerId !== null ? UserId::fromString($request->ownerId) : null;

        $query = new ParkingSearchQuery(
            new GeoLocation($request->latitude, $request->longitude),
            $request->radiusKm,
            $request->at,
            $request->minimumFreeSpots,
            $request->maxPriceCents,
            $ownerId,
            $request->name,
            $request->endsAt
        );

        $parkings = $this->parkingRepository->searchAvailable($query);

        $items = [];
        foreach ($parkings as $parking) {
            $free = $this->parkingRepository->getAvailabilityAt($parking->getId(), $request->at);
            $items[] = [
                'parking_id' => $parking->getId()->getValue(),
                'name' => $parking->getName(),
                'address' => $parking->getAddress(),
                'description' => $parking->getDescription(),
                'latitude' => $parking->getLocation()->getLatitude(),
                'longitude' => $parking->getLocation()->getLongitude(),
                'free_spots' => $free,
                'total_capacity' => $parking->getTotalCapacity(),
            ];
        }

        return $items;
    }
}
