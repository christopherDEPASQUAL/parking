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
        public readonly ?string $ownerId = null,
        public readonly ?string $name = null,
        public readonly ?\DateTimeImmutable $endsAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        $latitude = $data['latitude'] ?? $data['lat'] ?? null;
        $longitude = $data['longitude'] ?? $data['lng'] ?? null;
        $radius = $data['radius_km'] ?? $data['radius'] ?? null;
        $at = $data['at'] ?? $data['starts_at'] ?? null;
        $endsAt = $data['ends_at'] ?? null;
        $name = $data['name'] ?? $data['q'] ?? null;

        return new self(
            (float) ($latitude ?? throw new \InvalidArgumentException('latitude is required')),
            (float) ($longitude ?? throw new \InvalidArgumentException('longitude is required')),
            (float) ($radius ?? throw new \InvalidArgumentException('radius_km is required')),
            new \DateTimeImmutable($at ?? 'now'),
            (int) ($data['minimum_free_spots'] ?? 1),
            isset($data['max_price_cents']) ? (int) $data['max_price_cents'] : null,
            $data['owner_id'] ?? null,
            is_string($name) && $name !== '' ? $name : null,
            is_string($endsAt) && $endsAt !== '' ? new \DateTimeImmutable($endsAt) : null
        );
    }
}
