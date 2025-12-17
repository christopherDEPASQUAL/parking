<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

/**
 * Données d'entrée pour modifier la capacité d'un parking.
 */
final class UpdateParkingCapacityRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly int $newCapacity
    ) {
    }
}
