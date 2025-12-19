<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class SearchParkingsRequest
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?float $radiusKm = null,
        public readonly ?string $at = null,
        public readonly ?int $minAvailableSpots = null
    ) {}
}

