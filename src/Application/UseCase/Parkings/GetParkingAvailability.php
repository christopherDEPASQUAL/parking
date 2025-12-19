<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\GetParkingAvailabilityRequest;
use App\Application\DTO\Parkings\GetParkingAvailabilityResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

/**
 * Cas d'usage : obtenir la disponibilité d'un parking à une date/heure.
 * Utilise la logique métier de l'entité Parking (freeSpotsAt) plutôt qu'un calcul SQL.
 */
final class GetParkingAvailability
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    public function execute(GetParkingAvailabilityRequest $request): GetParkingAvailabilityResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $parking = $this->parkingRepository->findById($parkingId);

        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        // Le repository fournit les objets nécessaires pour la logique métier (réservations, abonnements, stationnements)
        $context = $this->parkingRepository->getAvailabilityContext($parkingId, $request->at);
        $reservations = $context['reservations'] ?? [];
        $abonnements = $context['abonnements'] ?? [];
        $stationnements = $context['stationnements'] ?? [];

        $freeSpots = $parking->freeSpotsAt($request->at, $reservations, $abonnements, $stationnements);

        return new GetParkingAvailabilityResponse(
            $parkingId->getValue(),
            $request->at,
            $freeSpots,
            $parking->getTotalCapacity()
        );
    }
}
