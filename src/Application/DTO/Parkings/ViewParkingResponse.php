<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class ViewParkingResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $address,
        public readonly ?string $description,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $totalCapacity,
        public readonly array $pricingPlan,
        public readonly array $openingSchedule,
        public readonly string $ownerId,
        public readonly string $createdAt
    ) {}
}

