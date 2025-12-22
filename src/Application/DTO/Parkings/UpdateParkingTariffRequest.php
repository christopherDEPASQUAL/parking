<?php declare(strict_types=1);

namespace App\Application\DTO\Parkings;

/**
 * Données d'entrée pour modifier la grille tarifaire d'un parking.
 *
 * @param array<int, array{upToMinutes:int, pricePerStepCents:int}> $pricingTiers
 */
final class UpdateParkingTariffRequest
{
    /**
     * @param array<int, array{upToMinutes:int, pricePerStepCents:int}> $pricingTiers
     * @param array<string, int>                                      $subscriptionPrices
     */
    public function __construct(
        public readonly string $parkingId,
        public readonly array $pricingTiers,
        public readonly int $defaultPricePerStepCents,
        public readonly ?int $overstayPenaltyCents = null,
        public readonly array $subscriptionPrices = []
    ) {
    }
}
