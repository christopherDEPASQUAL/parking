<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ListParkingStationnementsResponse
{
    /**
     * @param array<int, array{id: string, userId: string, spotId: string, startedAt: string, endedAt: string|null, durationMinutes: int, isActive: bool}> $stationnements
     */
    public function __construct(
        public readonly array $stationnements
    ) {}
}

