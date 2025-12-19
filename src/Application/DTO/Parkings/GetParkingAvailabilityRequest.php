<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;
/**
 * DTO: Input (parkingId, dateRange).
 *
 * Role:
 *  - Boundary type between Presentation and Application layers.
 *  - No framework dependencies.
 */

final class GetParkingAvailabilityRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly \DateTimeImmutable $at
    ) {}
}
