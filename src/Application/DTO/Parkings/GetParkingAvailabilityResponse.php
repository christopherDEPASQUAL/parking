<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

/**
 * DTO: Output (slots, counters).
 *
 * Role:
 *  - Boundary type between Presentation and Application layers.
 *  - No framework dependencies.
 */

final class GetParkingAvailabilityResponse
{
    public function __construct(
        public readonly string $parkingId,
        public readonly \DateTimeImmutable $at,
        public readonly int $freeSpots,
        public readonly int $totalCapacity
    ) {}
}

