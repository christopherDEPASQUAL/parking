<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

/**
 * Données d'entrée pour créer un parking.
 */
final class CreateParkingRequest
{
    /**
     * @param array<int, array{upToMinutes:int, pricePerStepCents:int}> $pricingTiers
     * @param array<int, array<int, array{start:string,end:string}>>    $openingHours day(0-6) => [{start,end}]
     */
    public function __construct(
        public readonly string $ownerId,
        public readonly string $name,
        public readonly string $address,
        public readonly int $capacity,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly array $pricingTiers,
        public readonly int $defaultPricePerStepCents,
        public readonly ?int $overstayPenaltyCents = null,
        public readonly array $openingHours = [],
        public readonly ?string $description = null,
    ) {}
}
