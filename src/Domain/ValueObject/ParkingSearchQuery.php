<?php declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Query object pour rechercher des parkings disponibles.
 *
 * Avantages:
 *  - centralise les invariants de recherche (rayon > 0, minPlaces >= 1, date non nulle)
 *  - extensible (prix max, tri, pagination) sans casser la signature du repository
 */
final class ParkingSearchQuery
{
    private GeoLocation $center;
    private float $radiusKm;
    private DateTimeImmutable $at;
    private int $minimumFreeSpots;
    private ?int $maxPriceCents;
    private ?UserId $ownerId;

    public function __construct(
        GeoLocation $center,
        float $radiusKm,
        DateTimeImmutable $at,
        int $minimumFreeSpots = 1,
        ?int $maxPriceCents = null,
        ?UserId $ownerId = null
    ) {
        if ($radiusKm <= 0) {
            throw new InvalidArgumentException('Search radius must be positive.');
        }

        if ($minimumFreeSpots < 1) {
            throw new InvalidArgumentException('Minimum free spots must be >= 1.');
        }

        if ($maxPriceCents !== null && $maxPriceCents < 0) {
            throw new InvalidArgumentException('Max price must be null or positive.');
        }

        $this->center = $center;
        $this->radiusKm = $radiusKm;
        $this->at = $at;
        $this->minimumFreeSpots = $minimumFreeSpots;
        $this->maxPriceCents = $maxPriceCents;
        $this->ownerId = $ownerId;
    }

    public function center(): GeoLocation
    {
        return $this->center;
    }

    public function radiusKm(): float
    {
        return $this->radiusKm;
    }

    public function at(): DateTimeImmutable
    {
        return $this->at;
    }

    public function minimumFreeSpots(): int
    {
        return $this->minimumFreeSpots;
    }

    public function maxPriceCents(): ?int
    {
        return $this->maxPriceCents;
    }

    public function ownerId(): ?UserId
    {
        return $this->ownerId;
    }
}
