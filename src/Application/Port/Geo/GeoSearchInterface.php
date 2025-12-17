<?php declare(strict_types=1);

namespace App\Application\Port\Geo;

use App\Domain\ValueObject\GeoLocation;

/**
 * Port : recherche géo pour trouver les parkings proches d'un point.
 */
interface GeoSearchInterface
{
    /**
     * @return array<int, array{parkingId:string,distanceKm:float}>
     */
    public function searchNearby(GeoLocation $center, float $radiusKm, int $limit = 20): array;
}
