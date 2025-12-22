<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\ListOutOfSlotDriversRequest;
use App\Application\DTO\Parkings\ListOutOfSlotDriversResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class ListOutOfSlotDrivers
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly ParkingSessionRepositoryInterface $sessionRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(ListOutOfSlotDriversRequest $request): ListOutOfSlotDriversResponse
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $ownerId = UserId::fromString($request->ownerId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        if (!$parking->getUserId()->equals($ownerId)) {
            throw new ValidationException('Vous n\'êtes pas propriétaire de ce parking.');
        }

        $now = new DateTimeImmutable();
        $activeSessions = $this->sessionRepository->listActiveAt($parkingId, $now);

        $outOfSlotDrivers = [];

        foreach ($activeSessions as $session) {
            $userId = $session->getUserId();
            $startedAt = $session->getStartedAt();

            $hasActiveReservation = $this->hasActiveReservation($userId, $parkingId, $startedAt);
            $hasActiveAbonnement = $this->hasActiveAbonnement($userId, $parkingId, $startedAt);

            if (!$hasActiveReservation && !$hasActiveAbonnement) {
                $user = $this->userRepository->findById($userId);
                if ($user !== null) {
                    $outOfSlotDrivers[] = [
                        'userId' => $userId->getValue(),
                        'userName' => $user->getFirstName() . ' ' . $user->getLastName(),
                        'sessionId' => $session->getId()->getValue(),
                        'startedAt' => $startedAt->format('Y-m-d H:i:s')
                    ];
                }
            }
        }

        return new ListOutOfSlotDriversResponse($outOfSlotDrivers);
    }

    private function hasActiveReservation(UserId $userId, ParkingId $parkingId, DateTimeImmutable $at): bool
    {
        $reservations = $this->reservationRepository->listByUser($userId);
        
        foreach ($reservations as $reservation) {
            if (!$reservation->parkingId()->equals($parkingId)) {
                continue;
            }
            
            if (!$reservation->isActive()) {
                continue;
            }
            
            $dateRange = $reservation->dateRange();
            if ($at >= $dateRange->getStart() && $at <= $dateRange->getEnd()) {
                return true;
            }
        }
        
        return false;
    }

    private function hasActiveAbonnement(UserId $userId, ParkingId $parkingId, DateTimeImmutable $at): bool
    {
        $abonnements = $this->abonnementRepository->listByUser($userId);
        
        foreach ($abonnements as $abonnement) {
            if (!$abonnement->parkingId()->equals($parkingId)) {
                continue;
            }
            
            if ($abonnement->coversTimeSlot($at)) {
                return true;
            }
        }
        
        return false;
    }
}

