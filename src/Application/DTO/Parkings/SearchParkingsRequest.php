<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class SearchParkingsRequest
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $radiusKm,
        public readonly \DateTimeImmutable $at,
        public readonly int $minimumFreeSpots = 1,
        public readonly ?int $maxPriceCents = null,
        public readonly ?string $ownerId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['latitude'] ?? throw new \InvalidArgumentException('latitude is required')),
            (float) ($data['longitude'] ?? throw new \InvalidArgumentException('longitude is required')),
            (float) ($data['radius_km'] ?? throw new \InvalidArgumentException('radius_km is required')),
            new \DateTimeImmutable($data['at'] ?? 'now'),
            (int) ($data['minimum_free_spots'] ?? 1),
            isset($data['max_price_cents']) ? (int) $data['max_price_cents'] : null,
            $data['owner_id'] ?? null
        );
    }
}
