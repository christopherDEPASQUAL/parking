<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\EnterParkingRequest;
use App\Application\DTO\Parkings\EnterParkingResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ParkingSpotId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\AbonnementId;
use DateTimeImmutable;

/**
 * Cas d'usage : Entrer dans un parking.
 * 
 * Règles métier :
 * - L'utilisateur doit avoir une réservation active OU un abonnement actif
 * - Le parking doit être ouvert
 * - Il ne doit pas déjà avoir un stationnement actif dans ce parking
 */
final class EnterParking
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly ParkingSessionRepositoryInterface $sessionRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(EnterParkingRequest $request): EnterParkingResponse
    {
        $userId = UserId::fromString($request->userId);
        $parkingId = ParkingId::fromString($request->parkingId);
        $spotId = ParkingSpotId::fromString($request->spotId);
        $enteredAt = $request->enteredAt 
            ? new DateTimeImmutable($request->enteredAt) 
            : new DateTimeImmutable();

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new ValidationException('Utilisateur introuvable.');
        }

        // Vérifier que le parking existe
        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        // Vérifier que le parking est ouvert
        if (!$parking->isOpenAt($enteredAt)) {
            throw new ValidationException('Le parking est fermé à cet instant.');
        }

        // Vérifier qu'il n'y a pas déjà un stationnement actif
        $activeSession = $this->sessionRepository->findActiveByUserAndParking($userId, $parkingId);
        if ($activeSession !== null) {
            throw new ValidationException('Vous avez déjà un stationnement actif dans ce parking.');
        }

        // Vérifier qu'il a une réservation active OU un abonnement actif
        $hasActiveReservation = $this->hasActiveReservation($userId, $parkingId, $enteredAt);
        $hasActiveAbonnement = $this->hasActiveAbonnement($userId, $parkingId, $enteredAt);

        if (!$hasActiveReservation && !$hasActiveAbonnement) {
            throw new ValidationException('Vous devez avoir une réservation active ou un abonnement actif pour entrer dans ce parking.');
        }

        // Créer la session de stationnement
        $session = ParkingSession::start($parkingId, $userId, $spotId, $enteredAt);
        $this->sessionRepository->save($session);

        return new EnterParkingResponse(
            $session->getId()->getValue(),
            $parkingId->getValue(),
            $spotId->getValue(),
            $enteredAt->format('Y-m-d H:i:s')
        );
    }

    private function hasActiveReservation(UserId $userId, ParkingId $parkingId, DateTimeImmutable $at): bool
    {
        $reservations = $this->reservationRepository->listActiveAt($parkingId, $at);
        
        foreach ($reservations as $reservation) {
            if ($reservation->userId()->equals($userId)) {
                $dateRange = $reservation->dateRange();
                if ($at >= $dateRange->start() && $at <= $dateRange->end()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasActiveAbonnement(UserId $userId, ParkingId $parkingId, DateTimeImmutable $at): bool
    {
        $abonnements = $this->abonnementRepository->listByUser($userId);
        
        foreach ($abonnements as $abonnement) {
            if ($abonnement->parkingId()->equals($parkingId) && $abonnement->coversTimeSlot($at)) {
                return true;
            }
        }
        
        return false;
    }
}


            }
        }
        
        return false;
    }
}


            }
        }
        
        return false;
    }
}

