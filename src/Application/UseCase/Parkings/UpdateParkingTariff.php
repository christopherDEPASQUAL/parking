<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\UpdateParkingTariffRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PricingPlan;

/**
 * Cas d'usage : mettre a jour la grille tarifaire d'un parking.
 */
final class UpdateParkingTariff
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    public function execute(UpdateParkingTariffRequest $request): void
    {
        $id = ParkingId::fromString($request->parkingId);
        $parking = $this->parkingRepository->findById($id);

        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $pricingPlan = new PricingPlan(
            $request->pricingTiers,
            $request->defaultPricePerStepCents,
            $request->overstayPenaltyCents ?? 2000,
            $request->subscriptionPrices
        );

        $parking->changePricingPlan($pricingPlan);
        $this->parkingRepository->save($parking);
    }
}
