<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\ViewParkingRequest;
use App\Application\DTO\Parkings\ViewParkingResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

/**
 * Cas d'usage : Voir les informations d'un parking.
 */
final class ViewParking
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function execute(ViewParkingRequest $request): ViewParkingResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $parking = $this->parkingRepository->findById($parkingId);

        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        return new ViewParkingResponse(
            $parking->getId()->getValue(),
            $parking->getName(),
            $parking->getAddress(),
            $parking->getDescription(),
            $parking->getLocation()->getLatitude(),
            $parking->getLocation()->getLongitude(),
            $parking->getTotalCapacity(),
            $this->serializePricingPlan($parking->getPricingPlan()),
            $this->serializeOpeningSchedule($parking->getOpeningSchedule()),
            $parking->getUserId()->getValue(),
            $parking->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    private function serializePricingPlan($pricingPlan): array
    {
        // TODO: Implémenter la sérialisation du PricingPlan
        // Pour l'instant, retourner un tableau vide
        return [];
    }

    private function serializeOpeningSchedule($openingSchedule): array
    {
        // TODO: Implémenter la sérialisation de l'OpeningSchedule
        // Pour l'instant, retourner un tableau vide
        return [];
    }
}

