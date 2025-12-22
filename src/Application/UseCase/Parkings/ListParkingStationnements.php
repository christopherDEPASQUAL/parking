<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\ListParkingStationnementsRequest;
use App\Application\DTO\Parkings\ListParkingStationnementsResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

/**
 * Cas d'usage : Liste des stationnements d'un parking (pour propriétaire).
 */
final class ListParkingStationnements
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly ParkingSessionRepositoryInterface $sessionRepository
    ) {}

    public function execute(ListParkingStationnementsRequest $request): ListParkingStationnementsResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);

        // Vérifier que le parking existe
        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $sessions = $this->sessionRepository->listByParking($parkingId);

        $results = [];
        foreach ($sessions as $session) {
            $results[] = [
                'id' => $session->getId()->getValue(),
                'userId' => $session->getUserId()->getValue(),
                'spotId' => $session->getSpotId()->getValue(),
                'startedAt' => $session->getStartedAt()->format('Y-m-d H:i:s'),
                'endedAt' => $session->getEndedAt()?->format('Y-m-d H:i:s'),
                'durationMinutes' => $session->durationMinutes(),
                'isActive' => $session->isActive(),
            ];
        }

        // Trier par date de début (plus récent en premier)
        usort($results, fn($a, $b) => $b['startedAt'] <=> $a['startedAt']);

        return new ListParkingStationnementsResponse($results);
    }
}

