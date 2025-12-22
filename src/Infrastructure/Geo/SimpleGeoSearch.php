<?php declare(strict_types=1);

namespace App\Infrastructure\Geo;

use App\Application\Port\Geo\GeoSearchInterface;
use App\Domain\ValueObject\GeoLocation;

final class SimpleGeoSearch implements GeoSearchInterface
{
    public function searchNearby(GeoLocation $center, float $radiusKm, int $limit = 20): array
    {
        return [];
    }
}
