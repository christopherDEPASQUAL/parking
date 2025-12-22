<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\GetMonthlyRevenueRequest;
use App\Application\DTO\Parkings\GetMonthlyRevenueResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

/**
 * Cas d'usage : Obtenir le chiffre d'affaire mensuel d'un parking.
 */
final class GetMonthlyRevenue
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function execute(GetMonthlyRevenueRequest $request): GetMonthlyRevenueResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);

        // Vérifier que le parking existe
        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        // Valider le mois
        if ($request->month < 1 || $request->month > 12) {
            throw new ValidationException('Le mois doit être entre 1 et 12.');
        }

        // Valider l'année
        if ($request->year < 2000 || $request->year > 2100) {
            throw new ValidationException('L\'année doit être entre 2000 et 2100.');
        }

        $revenueCents = $this->parkingRepository->getMonthlyRevenueCents(
            $parkingId,
            $request->year,
            $request->month
        );

        return new GetMonthlyRevenueResponse(
            $parkingId->getValue(),
            $request->year,
            $request->month,
            $revenueCents
        );
    }
}

