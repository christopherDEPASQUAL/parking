<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\ListUserStationnementsRequest;
use App\Application\DTO\Parkings\ListUserStationnementsResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;

/**
 * Cas d'usage : Liste des stationnements d'un utilisateur.
 */
final class ListUserStationnements
{
    public function __construct(
        private readonly ParkingSessionRepositoryInterface $sessionRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(ListUserStationnementsRequest $request): ListUserStationnementsResponse
    {
        $userId = UserId::fromString($request->userId);

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new ValidationException('Utilisateur introuvable.');
        }

        $sessions = $this->sessionRepository->listByUser($userId);

        $results = [];
        foreach ($sessions as $session) {
            $results[] = [
                'id' => $session->getId()->getValue(),
                'parkingId' => $session->getParkingId()->getValue(),
                'spotId' => $session->getSpotId()->getValue(),
                'startedAt' => $session->getStartedAt()->format('Y-m-d H:i:s'),
                'endedAt' => $session->getEndedAt()?->format('Y-m-d H:i:s'),
                'durationMinutes' => $session->durationMinutes(),
                'isActive' => $session->isActive(),
            ];
        }

        // Trier par date de début (plus récent en premier)
        usort($results, fn($a, $b) => $b['startedAt'] <=> $a['startedAt']);

        return new ListUserStationnementsResponse($results);
    }
}

