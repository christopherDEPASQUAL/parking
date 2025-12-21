<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

final class AddAbonnementTypeRequest
{
    public function __construct(
        public readonly string $parkingId,
        public readonly string $ownerId,
        public readonly string $name,
        public readonly array $weeklyTimeSlots,
        public readonly int $priceCents
    ) {}
}

