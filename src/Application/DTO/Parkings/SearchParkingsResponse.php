<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class SearchParkingsResponse
{
    /**
     * @param array<int, array{id: string, name: string, address: string, latitude: float, longitude: float, availableSpots: int, totalCapacity: int, distanceKm: float}> $parkings
     */
    public function __construct(
        public readonly array $parkings
    ) {}
}

