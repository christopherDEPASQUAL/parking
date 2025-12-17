<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;
/**
 * DTO: Create parking output (parkingId, createdAt).
 *
 * Role:
 *  - Boundary type between Presentation and Application layers.
 *  - No framework dependencies.
 */

final class CreateParkingResponse
{
    public function __construct(
        public readonly string $parkingId,
        public readonly int $totalCapacity,
        public readonly \DateTimeImmutable $createdAt
    ) {}
}
